<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    public function index()
    {
        return view('admin.plans.index', [
            'plans' => Plan::with('branch')->withCount(['subscriptions as active_count' => fn ($q) => $q->whereIn('status', ['active', 'trial', 'grace'])])->orderBy('sort')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.plans.form', [
            'plan' => new Plan(['is_active' => true, 'limits' => array_fill_keys(array_keys(Plan::LIMITS), -1), 'features' => []]),
            'branches' => Branch::orderBy('store_name')->get(['id', 'store_name', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $plan = Plan::create($this->validated($request));
        ActivityLog::record('plan.created', "Plan created: {$plan->name}", $plan);

        return redirect()->route('admin.plans.index')->with('success', 'Plan created.');
    }

    public function edit(Plan $plan)
    {
        return view('admin.plans.form', ['plan' => $plan, 'branches' => Branch::orderBy('store_name')->get(['id', 'store_name', 'name'])]);
    }

    public function update(Request $request, Plan $plan)
    {
        $plan->update($this->validated($request, $plan));
        ActivityLog::record('plan.updated', "Plan updated: {$plan->name}", $plan);

        return redirect()->route('admin.plans.index')->with('success', 'Plan updated. Changes apply to all branches on this plan immediately.');
    }

    public function destroy(Plan $plan)
    {
        if ($plan->subscriptions()->exists()) {
            return back()->with('warning', 'Plan has subscribers — deactivate it instead.');
        }
        if ($plan->is_free && Plan::where('is_free', true)->whereNull('branch_id')->count() <= 1) {
            return back()->with('warning', 'At least one public Free plan is required (used after expiry / before payment).');
        }
        $plan->delete();

        return back()->with('success', 'Plan deleted.');
    }

    protected function validated(Request $request, ?Plan $plan = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:255'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'price_lifetime' => ['nullable', 'numeric', 'min:0'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'commission_percent' => ['required', 'numeric', 'min:0', 'max:50'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'limits' => ['required', 'array'],
            'limits.*' => ['required', 'integer', 'min:-1'],
            'features' => ['nullable', 'array'],
            'features.*' => [Rule::in(array_keys(Plan::FEATURES))],
        ]);

        $data['slug'] = $plan?->slug ?? $this->slug($data['name']);
        $data['is_free'] = $request->boolean('is_free');
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['sort'] = (int) ($data['sort'] ?? 0);
        $data['limits'] = collect(Plan::LIMITS)->keys()->mapWithKeys(fn ($k) => [$k => (int) ($data['limits'][$k] ?? 0)])->all();
        $data['features'] = collect(Plan::FEATURES)->keys()->mapWithKeys(fn ($k) => [$k => in_array($k, $data['features'] ?? [], true)])->all();
        if ($data['is_free']) {
            $data['price_monthly'] = $data['price_yearly'] = 0;
            $data['price_lifetime'] = null;
            $data['trial_days'] = 0;
        }

        return $data;
    }

    protected function slug(string $name): string
    {
        $base = Str::slug($name) ?: 'plan';
        $slug = $base;
        $i = 2;
        while (Plan::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
