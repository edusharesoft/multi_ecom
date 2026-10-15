@extends('layouts.admin')
@section('title', $branch->display_name)

@section('content')
<a href="{{ route('admin.branches.index') }}" class="small text-muted"><i class="fa-solid fa-arrow-left"></i> All branches</a>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 my-3">
    <div>
        <h4 class="fw-bold mb-0">{{ $branch->display_name }} @include('admin.branches._state', ['b' => $branch])</h4>
        <a href="{{ $branch->url }}" target="_blank" class="small">{{ $branch->url }}</a>
        @if ($branch->isSuspended())<div class="small text-danger">Suspended {{ $branch->suspended_at->diffForHumans() }}{{ $branch->suspend_reason ? ' — '.$branch->suspend_reason : '' }}</div>@endif
    </div>
    <div class="d-flex gap-1">
        <a href="{{ route('admin.branches.edit', $branch) }}" class="btn btn-sm btn-dark"><i class="fa-solid fa-pen"></i> Edit</a>
        @include('admin.branches._actions', ['b' => $branch])
    </div>
</div>

<div class="row g-3 mb-4">
    @foreach ([['Products', $branch->products_count], ['Orders', $branch->orders_count], ['Pending Orders', $stats['pending']], ['Sales', '৳ '.number_format($stats['sales'], 2)], ['Paid Revenue', '৳ '.number_format($stats['paid'], 2)]] as [$l, $v])
        <div class="col"><div class="stat-card"><div><div class="v">{{ $v }}</div><div class="l">{{ $l }}</div></div></div></div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="panel mb-3">
            <div class="panel-head">Subscription <a href="{{ route('admin.subscriptions.index', ['q' => $branch->slug]) }}" class="small">Manage</a></div>
            <div class="panel-body small">
                @if ($s = $branch->subscription)
                    <div class="fw-bold fs-6">{{ $s->plan->name }} <span class="badge bg-{{ $s->status_color }}">{{ $s->status_label }}</span></div>
                    <div>{{ ucfirst($s->billing_cycle) }} · ৳ {{ number_format($s->amount) }}</div>
                    <div>Expiry: {{ $s->ends_at?->format('d M Y') ?? 'Never' }} · Auto-renew {{ $s->auto_renew ? 'on' : 'off' }}</div>
                    <div>Staff: {{ $branch->staff_count }} · Storage: {{ round($branch->storage_used / 1048576, 1) }} MB</div>
                @else
                    <span class="text-muted">No plan selected yet (onboarding).</span>
                @endif
                @if ($branch->invoices->isNotEmpty())
                    <hr class="my-2">
                    @foreach ($branch->invoices as $inv)
                        <div class="d-flex justify-content-between"><a href="{{ route('admin.invoices.show', $inv) }}">{{ $inv->number }}</a><span>৳ {{ number_format($inv->total) }} <span class="badge bg-{{ $inv->status_color }}">{{ $inv->status }}</span></span></div>
                    @endforeach
                @endif
            </div>
        </div>
        <div class="panel mb-3">
            <div class="panel-head">Registration Info</div>
            <div class="panel-body small">
                <div class="mb-1"><span class="text-muted">(a) Name:</span> {{ $branch->name }}</div>
                <div class="mb-1"><span class="text-muted">(b) Mobile:</span> {{ $branch->mobile }} @if($branch->isVerified())<i class="fa-solid fa-circle-check text-success" title="Verified {{ $branch->mobile_verified_at }}"></i>@endif</div>
                <div class="mb-1"><span class="text-muted">(c) Email:</span> {{ $branch->email }}</div>
                <div class="mb-1"><span class="text-muted">(d) USER / URL:</span> /{{ $branch->slug }}</div>
                <div class="mb-1"><span class="text-muted">(e) Address:</span> {{ $branch->address }}</div>
                <hr>
                <div class="mb-1"><span class="text-muted">Payments:</span> {{ implode(', ', $branch->paymentMethods()) ?: '—' }}</div>
                <div class="mb-1"><span class="text-muted">Shipping:</span> @money($branch->shipping_charge) · Tax {{ $branch->tax_percent }}%</div>
                <div><span class="text-muted">Registered:</span> {{ $branch->created_at->format('d M Y, h:i A') }}</div>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.branches.destroy', $branch) }}" class="panel border border-danger" onsubmit="return confirm('Delete this branch with ALL products and orders?')">
            @csrf @method('DELETE')
            <div class="panel-head text-danger">Danger Zone</div>
            <div class="panel-body small">
                <p>Permanently delete the branch, its products and orders. Type <code>{{ $branch->slug }}</code> to confirm.</p>
                <div class="d-flex gap-2"><input name="confirm" class="form-control form-control-sm" autocomplete="off"><button class="btn btn-sm btn-danger">Delete</button></div>
            </div>
        </form>
    </div>
    <div class="col-lg-8">
        <div class="panel mb-3">
            <div class="panel-head">Recent Orders <a href="{{ route('admin.orders.index', ['branch' => $branch->id]) }}" class="small">All orders of this branch</a></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 small">
                    <tbody>
                    @forelse ($orders as $o)
                        <tr onclick="location='{{ route('admin.orders.show', $o) }}'" style="cursor:pointer">
                            <td><strong>{{ $o->order_number }}</strong></td><td>{{ $o->customer_name }}</td><td>@money($o->total)</td>
                            <td><span class="badge bg-{{ $o->payment_color }}">{{ $o->method_label }}</span></td>
                            <td><span class="badge bg-{{ $o->status_color }}">{{ ucfirst($o->status) }}</span></td>
                            <td class="text-muted">{{ $o->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td class="text-center text-muted py-4">No orders.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="panel">
            <div class="panel-head">Latest Products</div>
            <div class="table-responsive">
                <table class="table mb-0 small">
                    <tbody>
                    @forelse ($products as $p)
                        <tr>
                            <td><img src="{{ $p->image_url }}" class="thumb-sm"></td>
                            <td><a href="{{ route('store.product', [$branch->slug, $p->slug]) }}" target="_blank">{{ $p->name }}</a><br><span class="text-muted">{{ $p->category?->name }}</span></td>
                            <td>@money($p->price)</td>
                            <td><span class="badge bg-{{ $p->is_active ? 'success' : 'secondary' }}">{{ $p->is_active ? 'Active' : 'Hidden' }}</span></td>
                        </tr>
                    @empty
                        <tr><td class="text-center text-muted py-4">No products.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
