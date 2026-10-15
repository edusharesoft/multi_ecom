@extends('layouts.manager')
@section('title', 'Brands')

@section('content')
<div class="row g-4">
    @perm('products.add')
    <div class="col-lg-4">
        <form method="POST" action="{{ route('manager.brands.store') }}" enctype="multipart/form-data" class="panel">
            @csrf
            <div class="panel-head">Add Brand</div>
            <div class="panel-body">
                <input name="name" class="form-control form-control-sm mb-2" placeholder="Brand name" required>
                <input type="file" name="logo" accept="image/*" class="form-control form-control-sm mb-3">
                <button class="btn btn-brand btn-sm w-100">Add</button>
            </div>
        </form>
    </div>
    @endperm
    <div class="col-lg-8">
        <div class="panel">
            <table class="table mb-0 small align-middle">
                <thead class="table-light"><tr><th>Logo</th><th>Brand</th><th>Products</th><th>Active</th><th></th></tr></thead>
                <tbody>
                @forelse ($brands as $b)
                    <tr>
                        <td>@if($b->logo_url)<img src="{{ $b->logo_url }}" style="height:32px">@endif</td>
                        <td>
                            <form method="POST" action="{{ route('manager.brands.update', $b) }}" enctype="multipart/form-data" id="br-{{ $b->id }}" class="d-flex gap-1">@csrf @method('PUT')
                                <input name="name" value="{{ $b->name }}" class="form-control form-control-sm" required>
                                <input type="file" name="logo" accept="image/*" class="form-control form-control-sm" style="max-width:180px">
                            </form>
                        </td>
                        <td>{{ $b->products_count }}</td>
                        <td><input type="hidden" name="is_active" value="0" form="br-{{ $b->id }}"><input class="form-check-input" type="checkbox" name="is_active" value="1" form="br-{{ $b->id }}" @checked($b->is_active)></td>
                        <td class="text-end text-nowrap">
                            @perm('products.edit')<button form="br-{{ $b->id }}" class="btn btn-sm btn-light"><i class="fa-solid fa-floppy-disk"></i></button>@endperm
                            @perm('products.delete')<form method="POST" action="{{ route('manager.brands.destroy', $b) }}" class="d-inline" onsubmit="return confirm('Delete brand?')">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="fa-solid fa-trash"></i></button></form>@endperm
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No brands yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
