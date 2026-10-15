@extends('layouts.manager')
@section('title', 'Staff')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="small text-muted">{{ $staff->count() }} / {{ \App\Models\Plan::limitLabel($limit) }} staff (plan limit)</div>
    <div class="d-flex gap-2">
        <a href="{{ route('manager.roles.index') }}" class="btn btn-sm btn-outline-dark"><i class="fa-solid fa-user-shield"></i> Roles & Permissions</a>
        @perm('staff.add')<button class="btn btn-sm btn-brand" data-bs-toggle="collapse" data-bs-target="#addStaff"><i class="fa-solid fa-plus"></i> Add Staff</button>@endperm
    </div>
</div>

@perm('staff.add')
<div class="collapse {{ $errors->any() ? 'show' : '' }}" id="addStaff">
    <form method="POST" action="{{ route('manager.staff.store') }}" class="panel mb-4">
        @csrf
        <div class="panel-body row g-2">
            <div class="col-md-3"><input name="name" value="{{ old('name') }}" class="form-control form-control-sm" placeholder="Name *" required></div>
            <div class="col-md-3"><input type="email" name="email" value="{{ old('email') }}" class="form-control form-control-sm" placeholder="Login email *" required></div>
            <div class="col-md-2"><input name="mobile" value="{{ old('mobile') }}" class="form-control form-control-sm" placeholder="Mobile"></div>
            <div class="col-md-2"><input name="designation" value="{{ old('designation') }}" class="form-control form-control-sm" placeholder="Designation"></div>
            <div class="col-md-2"><select name="role_id" class="form-select form-select-sm" required>@foreach ($roles as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><input type="password" name="password" class="form-control form-control-sm" placeholder="Password (min 8) *" required autocomplete="new-password"></div>
            <div class="col-md-2"><button class="btn btn-sm btn-brand w-100">Save</button></div>
        </div>
    </form>
</div>
@endperm

<div class="panel">
    <div class="table-responsive">
        <table class="table mb-0 small align-middle">
            <thead class="table-light"><tr><th>Name</th><th>Login</th><th>Role</th><th>Status</th><th>Last login</th><th></th></tr></thead>
            <tbody>
            @forelse ($staff as $s)
                <tr>
                    <td><strong>{{ $s->name }}</strong><br><span class="text-muted">{{ $s->designation }} {{ $s->mobile }}</span></td>
                    <td>{{ $s->email }}</td>
                    <td>{{ $s->role?->name ?? '—' }}</td>
                    <td><span class="badge bg-{{ $s->is_active ? 'success' : 'secondary' }}">{{ $s->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="text-muted">{{ $s->last_login_at?->diffForHumans() ?? 'Never' }}{{ $s->last_login_ip ? ' · '.$s->last_login_ip : '' }}</td>
                    <td class="text-end text-nowrap">
                        @perm('staff.edit')<button class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#edit-{{ $s->id }}"><i class="fa-solid fa-pen"></i></button>@endperm
                        @perm('staff.delete')
                        <form method="POST" action="{{ route('manager.staff.destroy', $s) }}" class="d-inline" onsubmit="return confirm('Remove {{ $s->name }}?')">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="fa-solid fa-trash"></i></button></form>
                        @endperm
                    </td>
                </tr>
                @perm('staff.edit')
                <tr class="collapse" id="edit-{{ $s->id }}"><td colspan="6" class="bg-light">
                    <form method="POST" action="{{ route('manager.staff.update', $s) }}" class="row g-2">@csrf @method('PUT')
                        <div class="col-md-2"><input name="name" value="{{ $s->name }}" class="form-control form-control-sm" required></div>
                        <div class="col-md-3"><input type="email" name="email" value="{{ $s->email }}" class="form-control form-control-sm" required></div>
                        <div class="col-md-2"><input name="mobile" value="{{ $s->mobile }}" class="form-control form-control-sm" placeholder="Mobile"></div>
                        <div class="col-md-2"><select name="role_id" class="form-select form-select-sm">@foreach ($roles as $r)<option value="{{ $r->id }}" @selected($s->role_id === $r->id)>{{ $r->name }}</option>@endforeach</select></div>
                        <div class="col-md-2"><input type="password" name="password" class="form-control form-control-sm" placeholder="New password" autocomplete="new-password"></div>
                        <input type="hidden" name="designation" value="{{ $s->designation }}">
                        <div class="col-md-1 d-flex align-items-center"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($s->is_active) title="Active"></div>
                        <div class="col-12"><button class="btn btn-sm btn-dark">Update</button></div>
                    </form>
                </td></tr>
                @endperm
            @empty
                <tr><td colspan="6" class="text-center text-muted py-5">এখনও কোনো স্টাফ নেই। স্টাফরা নিজেদের ইমেইল ও পাসওয়ার্ড দিয়ে <code>/login</code> থেকে লগইন করবেন।</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
