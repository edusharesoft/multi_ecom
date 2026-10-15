<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Staff;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StaffController extends Controller
{
    public function index()
    {
        $branch = Tenancy::branch();

        return view('manager.staff.index', [
            'staff' => $branch->staff()->with('role')->latest()->get(),
            'roles' => $branch->roles()->orderBy('name')->get(),
            'limit' => $branch->planLimit('max_staff'),
        ]);
    }

    public function store(Request $request)
    {
        $branch = Tenancy::branch();
        if (! $branch->withinLimit('max_staff')) {
            return back()->with('warning', "আপনার প্ল্যানে সর্বোচ্চ {$branch->planLimit('max_staff')} জন স্টাফ রাখা যায়। আপগ্রেড করুন।");
        }

        $data = $this->validated($request);
        $staff = $branch->staff()->create($data);
        ActivityLog::record('staff.created', "Staff added: {$staff->name} ({$staff->role?->name})", $staff);

        return back()->with('success', "{$staff->name} যোগ হয়েছে। লগইন: {$staff->email}");
    }

    public function update(Request $request, Staff $staff)
    {
        $this->own($staff);
        abort_unless(Tenancy::canGrant($staff->role?->permissions ?? []), 403);
        $data = $this->validated($request, $staff);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $staff->update($data);
        ActivityLog::record('staff.updated', "Staff updated: {$staff->name}", $staff);

        return back()->with('success', 'স্টাফ আপডেট হয়েছে।');
    }

    public function destroy(Staff $staff)
    {
        $this->own($staff);
        abort_unless(Tenancy::canGrant($staff->role?->permissions ?? []), 403);
        abort_if(Tenancy::actor() instanceof Staff && Tenancy::actor()->id === $staff->id, 400, 'You cannot delete yourself.');
        $staff->delete();
        ActivityLog::record('staff.deleted', "Staff removed: {$staff->name}");

        return back()->with('success', 'স্টাফ মুছে ফেলা হয়েছে।');
    }

    protected function validated(Request $request, ?Staff $staff = null): array
    {
        $branch = Tenancy::branch();
        $request->merge(['email' => strtolower(trim((string) $request->email))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('staff', 'email')->ignore($staff?->id), Rule::unique('branches', 'email')],
            'mobile' => ['nullable', 'regex:/^01[3-9]\d{8}$/'],
            'designation' => ['nullable', 'string', 'max:80'],
            'role_id' => ['required', Rule::exists('roles', 'id')->where('branch_id', $branch->id)],
            'password' => [$staff ? 'nullable' : 'required', Password::min(8)],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        abort_unless(Tenancy::canGrant(\App\Models\Role::find($data['role_id'])?->permissions ?? []), 403, 'You cannot assign a role with more permissions than your own.');

        return $data;
    }

    protected function own(Staff $staff): void
    {
        abort_unless($staff->branch_id === Tenancy::branch()->id, 404);
    }
}
