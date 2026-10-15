@extends('layouts.manager')
@section('title', 'Shipments')

@section('content')
<div class="row g-3 mb-3">
    @foreach (\App\Models\Shipment::GROUPS as $k => [$label, $color])
        <div class="col-6 col-lg"><a href="{{ request()->fullUrlWithQuery(['group' => $k, 'page' => null]) }}" class="stat-card text-decoration-none text-dark">
            <div><div class="v">{{ $counts[$k]->c ?? 0 }}</div><div class="l"><span class="badge bg-{{ $color }}">{{ $label }}</span> <span class="text-muted">COD ৳{{ number_format($counts[$k]->cod ?? 0) }}</span></div></div></a></div>
    @endforeach
</div>
<div class="panel">
    <div class="panel-head flex-wrap gap-2">
        <form class="d-flex gap-2 flex-wrap" method="GET">
            <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="width:220px" placeholder="Order / tracking / phone">
            <select name="courier" class="form-select form-select-sm" style="width:140px"><option value="">All couriers</option>@foreach (\App\Models\CourierAccount::COURIERS as $k => [$l])<option value="{{ $k }}" @selected(request('courier') === $k)>{{ $l }}</option>@endforeach</select>
            <select name="group" class="form-select form-select-sm" style="width:150px"><option value="">Any status</option>@foreach (\App\Models\Shipment::GROUPS as $k => [$l])<option value="{{ $k }}" @selected(request('group') === $k)>{{ $l }}</option>@endforeach</select>
            <button class="btn btn-sm btn-dark">Filter</button>
        </form>
        <div class="d-flex gap-2">
            @perm('orders.edit')<form method="POST" action="{{ route('manager.shipments.sync-all') }}">@csrf<button class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-rotate"></i> Sync all open</button></form>@endperm
            @perm('settings.edit')<a href="{{ route('manager.courier.settings') }}" class="btn btn-sm btn-light border"><i class="fa-solid fa-gear"></i> Courier settings</a>@endperm
        </div>
    </div>
    <div class="table-responsive"><table class="table table-hover mb-0 small align-middle">
        <thead class="table-light"><tr><th>Order</th><th>Customer</th><th>Courier</th><th>Tracking</th><th>Status</th><th class="text-end">COD</th><th>Updated</th></tr></thead>
        @forelse ($shipments as $s)
            <tr>
                <td><a href="{{ route('manager.orders.show', $s->order) }}"><strong>{{ $s->order?->order_number }}</strong></a><br><span class="text-muted">{{ $s->created_at->format('d M, h:i A') }}</span></td>
                <td>{{ $s->order?->customer_name }}<br><span class="text-muted">{{ $s->order?->customer_phone }}</span></td>
                <td>{{ $s->courierLabel() }}</td>
                <td>{{ $s->tracking_code ?: $s->consignment_id ?: '—' }} @if ($u = $s->publicTrackingUrl())<a href="{{ $u }}" target="_blank"><i class="fa-solid fa-up-right-from-square"></i></a>@endif</td>
                <td><span class="badge bg-{{ \App\Models\Shipment::GROUPS[$s->status_group][1] }}">{{ \App\Models\Shipment::GROUPS[$s->status_group][0] }}</span><br><span class="text-muted">{{ $s->status }}</span></td>
                <td class="text-end">{{ number_format($s->cod_amount, 0) }}</td>
                <td class="text-muted">{{ ($s->last_synced_at ?? $s->updated_at)->diffForHumans() }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-5">এখনো কোনো পার্সেল বুক হয়নি। অর্ডার পাতা থেকে “Send to Courier” চাপুন।</td></tr>
        @endforelse
    </table></div>
    <div class="panel-body">{{ $shipments->links() }}</div>
</div>
@endsection
