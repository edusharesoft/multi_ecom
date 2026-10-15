@extends('layouts.admin')
@section('title', 'Edit Branch')

@section('content')
<form method="POST" action="{{ route('admin.branches.update', $branch) }}" enctype="multipart/form-data" class="panel" style="max-width:780px">
    @csrf @method('PUT')
    <div class="panel-head">{{ $branch->display_name }}</div>
    <div class="panel-body row g-3">
        <div class="col-md-6"><label class="form-label small fw-semibold">Store Name</label><input name="store_name" value="{{ old('store_name', $branch->store_name) }}" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Manager Name</label><input name="name" value="{{ old('name', $branch->name) }}" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Mobile</label><input name="mobile" value="{{ old('mobile', $branch->mobile) }}" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Email</label><input type="email" name="email" value="{{ old('email', $branch->email) }}" class="form-control" required></div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">USER / Sub Folder</label>
            <div class="input-group"><span class="input-group-text">{{ preg_replace('#^https?://#', '', url('/')) }}/</span><input name="slug" value="{{ old('slug', $branch->slug) }}" class="form-control" required></div>
            <div class="form-text text-warning">পরিবর্তন করলে পুরনো লিংক কাজ করবে না।</div>
        </div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Storefront</label>
            <select name="status" class="form-select">
                <option value="active" @selected(old('status', $branch->status) === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $branch->status) === 'inactive')>Hidden</option>
            </select>
        </div>
        <div class="col-12"><label class="form-label small fw-semibold">Address</label><textarea name="address" rows="2" class="form-control" required>{{ old('address', $branch->address) }}</textarea></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Store Logo</label>
            @if ($branch->logo_url)
                <div class="d-flex align-items-center gap-2 mb-2"><img src="{{ $branch->logo_url }}" class="rounded border" style="height:48px;max-width:140px;object-fit:contain;background:#f3f4f6">
                    <div class="form-check small"><input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="rmlogo"><label class="form-check-label text-danger" for="rmlogo">Remove</label></div></div>
            @endif
            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="form-control form-control-sm">
            <div class="form-text">স্টোর, লগইন পেইজ ও ম্যানেজার প্যানেলে দেখাবে।</div>
        </div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Reset Password</label><input type="password" name="password" class="form-control" placeholder="Leave empty to keep" autocomplete="new-password"></div>
    </div>
    <div class="panel-body border-top d-flex justify-content-between">
        <a href="{{ route('admin.branches.show', $branch) }}" class="btn btn-link">Cancel</a>
        <button class="btn btn-primary px-4">Save</button>
    </div>
</form>
@endsection
