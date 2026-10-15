@extends('layouts.manager')
@section('title', 'Receivables')

@section('content')
@include('manager.reports._nav')
<div class="d-flex justify-content-end gap-2 mb-2 d-print-none">
    @perm('reports.export')<a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-file-csv"></i> CSV</a>@endperm
    <button onclick="window.print()" class="btn btn-sm btn-outline-dark"><i class="fa-solid fa-print"></i> Print</button>
</div>
<div class="panel">
    <div class="panel-head">যে অর্ডারের টাকা এখনো পাওয়া যায়নি (COD / ভেরিফিকেশন বাকি)
        <strong class="text-danger">@money($rows->sum(fn ($o) => $o->payable))</strong></div>
    <div class="table-responsive"><table class="table table-sm table-hover mb-0 small align-middle">
        <thead class="table-light"><tr><th>Order</th><th>Customer</th><th>Method</th><th>Payment</th><th>Order status</th><th>Age</th><th class="text-end">Due</th></tr></thead>
        @forelse ($rows as $o)
            <tr onclick="location='{{ route('manager.orders.show', $o) }}'" style="cursor:pointer">
                <td><strong>{{ $o->order_number }}</strong><br><span class="text-muted">{{ $o->created_at->format('d M Y') }}</span></td>
                <td>{{ $o->customer_name }}<br><span class="text-muted">{{ $o->customer_phone }}</span></td>
                <td>{{ \App\Models\Order::METHOD_LABELS[$o->payment_method] ?? $o->payment_method }}</td>
                <td><span class="badge bg-{{ $o->payment_status === 'pending_verification' ? 'warning text-dark' : 'secondary' }}">{{ str_replace('_', ' ', $o->payment_status) }}</span></td>
                <td>{{ \App\Models\Order::STATUS_META[$o->status][0] ?? $o->status }}</td>
                <td class="{{ $o->created_at->diffInDays() > 7 ? 'text-danger' : '' }}">{{ (int) $o->created_at->diffInDays() }} days</td>
                <td class="text-end fw-semibold">{{ number_format($o->payable, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-5">কোনো বকেয়া নেই।</td></tr>
        @endforelse
    </table></div>
</div>
@endsection
