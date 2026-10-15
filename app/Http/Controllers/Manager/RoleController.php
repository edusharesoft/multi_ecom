<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Role;
use App\Support\Permissions;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        $branch = Tenancy::branch();
        Role::createDefaultsFor($branch);   // make sure templates exist

        return view('manager.staff.roles', ['roles' => $branch->roles()->withCount('staff')->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $role = Tenancy::branch()->roles()->create($data);
        ActivityLog::record('role.created', "Role created: {$role->name}", $role);

        return back()->with('success', 'রোল তৈরি হয়েছে।');
    }

    public function update(Request $request, Role $role)
    {
        $this->own($role);
        abort_unless(Tenancy::canGrant($role->permissions ?? []), 403);
        $role->update($this->validated($request, $role));
        ActivityLog::record('role.updated', "Role permissions updated: {$role->name}", $role, ['permissions' => $role->permissions]);

        return back()->with('success', "{$role->name} এর পারমিশন আপডেট হয়েছে।");
    }

    public function destroy(Role $role)
    {
        $this->own($role);
        abort_unless(Tenancy::canGrant($role->permissions ?? []), 403);
        if ($role->staff()->exists()) {
            return back()->with('warning', 'এই রোলে স্টাফ আছে — আগে তাদের অন্য রোলে সরান।');
        }
        $role->delete();
        ActivityLog::record('role.deleted', "Role deleted: {$role->name}");

        return back()->with('success', 'রোল মুছে ফেলা হয়েছে।');
    }

    protected function validated(Request $request, ?Role $role = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('roles', 'name')->where('branch_id', Tenancy::branch()->id)->ignore($role?->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in(Permissions::all())],
        ]);
        $data['permissions'] = array_values($data['permissions'] ?? []);
        abort_unless(Tenancy::canGrant($data['permissions']), 403, 'You cannot grant permissions you do not have.');

        return $data;
    }

    protected function own(Role $role): void
    {
        abort_unless($role->branch_id === Tenancy::branch()->id, 404);
    }
}
