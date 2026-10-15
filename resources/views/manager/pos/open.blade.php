@extends('layouts.manager')
@section('title', 'POS — Open Register')

@section('content')
@include('manager.pos._nav')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="panel">
            <div class="panel-head"><span><i class="fa-solid fa-cash-register"></i> ক্যাশ রেজিস্টার খুলুন</span></div>
            @perm('pos.add')
            <form method="POST" action="{{ route('manager.pos.open') }}" class="panel-body">@csrf
                <label class="form-label small mb-0">Warehouse / Counter</label>
                <select name="warehouse_id" class="form-select mb-3">@foreach ($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach</select>
                <label class="form-label small mb-0">Opening cash in drawer ৳</label>
                <input type="number" step="0.01" min="0" name="opening_cash" value="{{ $last?->counted_cash ?? 0 }}" class="form-control form-control-lg mb-3" required autofocus>
                @if ($last)<p class="small text-muted">শেষ সেশন: {{ $last->closed_at->format('d M Y h:i A') }} — counted ৳{{ number_format($last->counted_cash, 2) }}</p>@endif
                <button class="btn btn-brand w-100 btn-lg">Open Register</button>
            </form>
            @else
            <div class="panel-body small text-muted">রেজিস্টার খোলার অনুমতি আপনার নেই।</div>
            @endperm
        </div>
    </div>
</div>
@endsection
