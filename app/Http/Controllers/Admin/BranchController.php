<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::with('subscription.plan')->withCount(['products', 'orders'])
            ->withSum(['orders as sales' => fn ($q) => $q->where('status', '!=', 'cancelled')], 'total')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('store_name', 'like', '%'.$request->q.'%')->orWhere('name', 'like', '%'.$request->q.'%')
                ->orWhere('slug', 'like', '%'.$request->q.'%')->orWhere('email', 'like', '%'.$request->q.'%')
                ->orWhere('mobile', 'like', '%'.$request->q.'%')))
            ->when($request->state === 'live', fn ($q) => $q->live())
            ->when($request->state === 'unverified', fn ($q) => $q->whereNull('mobile_verified_at'))
            ->when($request->state === 'suspended', fn ($q) => $q->whereNotNull('suspended_at'))
            ->when($request->state === 'hidden', fn ($q) => $q->where('status', 'inactive'))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.branches.index', compact('branches'));
    }

    public function show(Branch $branch)
    {
        $branch->loadCount(['products', 'orders', 'staff']);
        $branch->load(['subscription.plan', 'invoices' => fn ($q) => $q->take(8)]);
        $stats = [
            'sales' => (float) $branch->orders()->where('status', '!=', 'cancelled')->sum('total'),
            'paid' => (float) $branch->orders()->where('payment_status', 'paid')->sum('total'),
            'pending' => $branch->orders()->whereIn('status', ['new', 'pending'])->count(),
        ];
        $orders = $branch->orders()->latest()->take(10)->get();
        $products = $branch->products()->with('category')->latest()->take(10)->get();

        return view('admin.branches.show', compact('branch', 'stats', 'orders', 'products'));
    }

    public function edit(Branch $branch)
    {
        return view('admin.branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        $request->merge([
            'slug' => Str::lower(trim((string) $request->slug)),
            'email' => Str::lower(trim((string) $request->email)),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'store_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('branches', 'email')->ignore($branch->id)],
            'mobile' => ['required', 'regex:/^01[3-9]\d{8}$/', Rule::unique('branches', 'mobile')->ignore($branch->id)],
            'slug' => ['required', 'regex:/^[a-z0-9][a-z0-9-]{1,58}[a-z0-9]$/', Rule::notIn(Branch::RESERVED_SLUGS), Rule::unique('branches', 'slug')->ignore($branch->id)],
            'address' => ['required', 'string', 'max:500'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'password' => ['nullable', Password::min(8)],
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }
        // Phase 12: Super Admin can set / remove the store logo (counted in the branch storage like the manager's own upload)
        unset($data['logo']);
        if ($request->hasFile('logo')) {
            $old = $branch->logo;
            $data['logo'] = $branch->storeUpload($request->file('logo'), "branches/{$branch->id}");
            $branch->deleteUpload($old);
        } elseif ($request->boolean('remove_logo') && $branch->logo) {
            $branch->deleteUpload($branch->logo);
            $data['logo'] = null;
        }
        $branch->update($data);

        return redirect()->route('admin.branches.show', $branch)->with('success', 'Branch updated.');
    }

    public function suspend(Request $request, Branch $branch)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);
        $branch->forceFill(['suspended_at' => now(), 'suspend_reason' => $data['reason'] ?? null])->save();
        \App\Models\ActivityLog::record('branch.suspended', "Branch suspended: {$branch->display_name}", $branch, ['reason' => $data['reason'] ?? null], $branch->id);

        return back()->with('success', "{$branch->display_name} suspended — storefront & manager login disabled.");
    }

    public function activate(Branch $branch)
    {
        $branch->forceFill(['suspended_at' => null, 'suspend_reason' => null, 'status' => 'active'])->save();
        \App\Models\ActivityLog::record('branch.activated', "Branch activated: {$branch->display_name}", $branch, [], $branch->id);

        return back()->with('success', "{$branch->display_name} is active again.");
    }

    public function verify(Branch $branch)
    {
        $branch->forceFill(['mobile_verified_at' => now(), 'otp_code' => null, 'otp_expires_at' => null])->save();

        return back()->with('success', 'Mobile marked as verified — store is live.');
    }

    /** Open the branch backend as this branch manager (support). */
    public function impersonate(Request $request, Branch $branch)
    {
        Auth::guard('branch')->login($branch);
        $request->session()->put('impersonating', $request->user('web')->id);
        \App\Models\ActivityLog::record('admin.impersonate', "Super Admin opened branch backend: {$branch->display_name}", $branch, [], $branch->id);

        return redirect()->route($branch->isVerified() ? 'manager.dashboard' : 'otp.show');
    }

    public function destroy(Request $request, Branch $branch)
    {
        $request->validate(['confirm' => ['required', Rule::in([$branch->slug])]], ['confirm.in' => 'Type the branch USER name to confirm.']);

        Storage::disk('public')->deleteDirectory("products/{$branch->id}");
        Storage::disk('public')->deleteDirectory("branches/{$branch->id}");
        \Illuminate\Support\Facades\DB::transaction(function () use ($branch) {
            // these rows RESTRICT deletes of suppliers / products / warehouses — remove them before the cascade
            \App\Models\Purchase::where('branch_id', $branch->id)->delete();
            \App\Models\PosSession::where('branch_id', $branch->id)->delete();
            if (\App\Services\AccountingService::ready()) {   // cash book rows RESTRICT deletes of accounts / categories
                \App\Models\AccountTransaction::where('branch_id', $branch->id)->delete();
                \App\Models\Voucher::where('branch_id', $branch->id)->delete();
                \App\Models\AccountTransfer::where('branch_id', $branch->id)->delete();
            }
            $branch->delete();   // products, orders cascade
        });

        return redirect()->route('admin.branches.index')->with('success', 'Branch deleted permanently.');
    }
}
