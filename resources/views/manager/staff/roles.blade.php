@extends('layouts.manager')
@section('title', 'Roles & Permissions')

@section('content')
<a href="{{ route('manager.staff.index') }}" class="small text-muted"><i class="fa-solid fa-arrow-left"></i> Staff</a>
<p class="small text-muted mt-2">Branch Admin (মালিক) সব কিছু করতে পারেন। নিচের রোলগুলো স্টাফদের জন্য — প্রতিটি মডিউলে View / Add / Edit / Delete / Approve / Export / Print আলাদাভাবে দিন।</p>

@php
$renderMatrix = function ($role = null) {
    $has = fn ($p) => $role ? in_array($p, $role->permissions ?? [], true) : false;
    $html = '<div class="table-responsive"><table class="table table-sm table-bordered small mb-2 text-center align-middle"><thead class="table-light"><tr><th class="text-start">Module</th>';
    foreach (\App\Support\Permissions::ACTIONS as $a) { $html .= '<th>'.ucfirst($a).'</th>'; }
    $html .= '</tr></thead><tbody>';
    foreach (\App\Support\Permissions::MODULES as $module => $actions) {
        $html .= '<tr><td class="text-start">'.e(\App\Support\Permissions::LABELS[$module]).'</td>';
        foreach (\App\Support\Permissions::ACTIONS as $a) {
            $p = "{$module}.{$a}";
            $html .= '<td>'.(in_array($a, $actions, true) ? '<input class="form-check-input" type="checkbox" name="permissions[]" value="'.$p.'"'.($has($p) ? ' checked' : '').'>' : '<span class="text-muted">—</span>').'</td>';
        }
        $html .= '</tr>';
    }

    return $html.'</tbody></table></div>';
};
@endphp

@foreach ($roles as $role)
    <form method="POST" action="{{ route('manager.roles.update', $role) }}" class="panel mb-3">
        @csrf @method('PUT')
        <div class="panel-head">
            <span><i class="fa-solid fa-user-tag text-brand"></i> {{ $role->name }} <small class="text-muted fw-normal">· {{ $role->staff_count }} staff</small></span>
            <button type="button" class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#role-{{ $role->id }}">Permissions</button>
        </div>
        <div class="collapse" id="role-{{ $role->id }}">
            <div class="panel-body">
                <div class="row g-2 mb-2">
                    <div class="col-md-4"><input name="name" value="{{ $role->name }}" class="form-control form-control-sm" required></div>
                    <div class="col-md-8"><input name="description" value="{{ $role->description }}" class="form-control form-control-sm" placeholder="Description"></div>
                </div>
                {!! $renderMatrix($role) !!}
                @perm('staff.edit')<button class="btn btn-sm btn-dark">Save Permissions</button>@endperm
                @perm('staff.delete')<button type="submit" form="del-role-{{ $role->id }}" class="btn btn-sm btn-link text-danger">Delete role</button>@endperm
            </div>
        </div>
    </form>
    <form method="POST" action="{{ route('manager.roles.destroy', $role) }}" id="del-role-{{ $role->id }}" onsubmit="return confirm('Delete role?')">@csrf @method('DELETE')</form>
@endforeach

@perm('staff.add')
<form method="POST" action="{{ route('manager.roles.store') }}" class="panel mt-4">
    @csrf
    <div class="panel-head">New Role</div>
    <div class="panel-body">
        <div class="row g-2 mb-2">
            <div class="col-md-4"><input name="name" class="form-control form-control-sm" placeholder="Role name" required></div>
            <div class="col-md-8"><input name="description" class="form-control form-control-sm" placeholder="Description"></div>
        </div>
        {!! $renderMatrix() !!}
        <button class="btn btn-sm btn-brand">Create Role</button>
    </div>
</form>
@endperm
@endsection
