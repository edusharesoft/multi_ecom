@extends('layouts.manager')
@section('title', __('Employees'))

@section('content')
@include('manager.hrm._nav')
<div class="row g-4">
    <div class="col-xl-8">
        <div class="panel">
            <form class="panel-head justify-content-start gap-2" method="GET">
                <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="width:220px" placeholder="Name / phone / code">
                <select name="status" class="form-select form-select-sm" style="width:130px"><option value="">Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select>
                <button class="btn btn-sm btn-dark">Filter</button>
            </form>
            <div class="table-responsive"><table class="table table-hover mb-0 small align-middle">
                <thead class="table-light"><tr><th></th><th>Name</th><th>Designation</th><th>Phone</th><th>Salary</th><th>Joined</th><th></th></tr></thead>
                @forelse ($employees as $e)
                    <tr>
                        <td style="width:44px">@if($e->photo_url)<img src="{{ $e->photo_url }}" alt="" class="rounded-circle" style="width:36px;height:36px;object-fit:cover">@else<span class="avatar d-inline-flex align-items-center justify-content-center rounded-circle bg-light" style="width:36px;height:36px">{{ mb_substr($e->name, 0, 1) }}</span>@endif</td>
                        <td><strong>{{ $e->name }}</strong> @if($e->code)<span class="text-muted">#{{ $e->code }}</span>@endif @if($e->staff)<br><span class="badge bg-light text-dark border"><i class="fa-solid fa-user-lock"></i> {{ $e->staff->email }}</span>@endif</td>
                        <td>{{ $e->designation }}<br><span class="text-muted">{{ $e->department }}</span></td>
                        <td>{{ $e->phone }}</td>
                        <td>@money($e->basic_salary)<span class="text-muted">/{{ $e->salary_type === 'daily' ? 'day' : 'month' }}</span>@if($e->allowance > 0)<br><span class="text-muted">+ {{ number_format($e->allowance) }} allowance</span>@endif</td>
                        <td>{{ $e->join_date?->format('d M Y') }}</td>
                        <td>@perm('hrm.edit')<a href="{{ route('manager.hrm.employees', ['edit' => $e->id]) }}" class="btn btn-sm btn-light"><i class="fa-solid fa-pen"></i></a>@endperm</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">এখনো কোনো কর্মচারী নেই।</td></tr>
                @endforelse
            </table></div>
        </div>
    </div>
    <div class="col-xl-4">
        @if (\App\Support\Tenancy::can($edit ? 'hrm.edit' : 'hrm.add'))
        <form method="POST" action="{{ $edit ? route('manager.hrm.employees.update', $edit) : route('manager.hrm.employees.store') }}" enctype="multipart/form-data" class="panel">@csrf @if($edit) @method('PUT') @endif
            <div class="panel-head">{{ $edit ? __('Edit employee') : __('Add employee') }} @if($edit)<a href="{{ route('manager.hrm.employees') }}" class="small">+ new</a>@endif</div>
            <div class="panel-body small">
                <div class="d-flex gap-2 mb-2"><input name="name" value="{{ old('name', $edit?->name) }}" class="form-control form-control-sm" placeholder="Full name *" required><input name="code" value="{{ old('code', $edit?->code) }}" class="form-control form-control-sm" placeholder="ID/Code" style="width:100px"></div>
                <div class="d-flex gap-2 mb-2"><input name="phone" value="{{ old('phone', $edit?->phone) }}" class="form-control form-control-sm" placeholder="Phone"><input type="email" name="email" value="{{ old('email', $edit?->email) }}" class="form-control form-control-sm" placeholder="Email"></div>
                <div class="d-flex gap-2 mb-2"><input name="designation" value="{{ old('designation', $edit?->designation) }}" class="form-control form-control-sm" placeholder="Designation (e.g. Salesman)"><input name="department" value="{{ old('department', $edit?->department) }}" class="form-control form-control-sm" placeholder="Department"></div>
                <label class="form-label mb-0">Join date</label><input type="date" name="join_date" value="{{ old('join_date', $edit?->join_date?->toDateString()) }}" class="form-control form-control-sm mb-2">
                <div class="d-flex gap-2 mb-2">
                    <div><label class="form-label mb-0">Salary type</label><select name="salary_type" class="form-select form-select-sm"><option value="monthly" @selected(old('salary_type', $edit?->salary_type) !== 'daily')>Monthly</option><option value="daily" @selected(old('salary_type', $edit?->salary_type) === 'daily')>Daily wage</option></select></div>
                    <div><label class="form-label mb-0">Basic ৳ *</label><input type="number" step="0.01" min="0" name="basic_salary" value="{{ old('basic_salary', $edit?->basic_salary ?? 0) }}" class="form-control form-control-sm" required></div>
                    <div><label class="form-label mb-0">Allowance ৳</label><input type="number" step="0.01" min="0" name="allowance" value="{{ old('allowance', $edit?->allowance ?? 0) }}" class="form-control form-control-sm"></div>
                </div>
                <label class="form-label mb-0">Login account (Staff)</label>
                <select name="staff_id" class="form-select form-select-sm mb-2"><option value="">— none —</option>@foreach ($staff as $s)<option value="{{ $s->id }}" @selected(old('staff_id', $edit?->staff_id) == $s->id)>{{ $s->name }} ({{ $s->email }})</option>@endforeach</select>
                <input name="address" value="{{ old('address', $edit?->address) }}" class="form-control form-control-sm mb-2" placeholder="Address">
                <input name="emergency_contact" value="{{ old('emergency_contact', $edit?->emergency_contact) }}" class="form-control form-control-sm mb-2" placeholder="Emergency contact (name, phone)">
                <textarea name="notes" rows="2" class="form-control form-control-sm mb-2" placeholder="Notes">{{ old('notes', $edit?->notes) }}</textarea>
                <label class="form-label mb-0">Photo</label><input type="file" name="photo_file" accept="image/*" class="form-control form-control-sm mb-2">
                <div class="form-check mb-3"><input type="hidden" name="is_active" value="0"><input type="checkbox" class="form-check-input" name="is_active" value="1" @checked(old('is_active', $edit?->is_active ?? true))><label class="form-check-label">Active</label></div>
                <button class="btn btn-brand btn-sm w-100">{{ __('Save') }}</button>
            </div>
        </form>
        @endif
    </div>
</div>
@endsection
