@extends('layouts.admin')
@section('title', 'Packages / Plans')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <p class="small text-muted mb-0">Limit <code>-1</code> = Unlimited. প্ল্যান এডিট করলে সেই প্ল্যানের সব ব্রাঞ্চে সাথে সাথে প্রযোজ্য হয়।</p>
    <a href="{{ route('admin.plans.create') }}" class="btn btn-sm btn-primary"><i class="fa-solid fa-plus"></i> New Plan</a>
</div>
<div class="panel">
    <div class="table-responsive">
        <table class="table mb-0 small align-middle">
            <thead class="table-light"><tr><th>Plan</th><th>Monthly</th><th>Yearly</th><th>Lifetime</th><th>Trial</th><th>Products / Staff / Orders</th><th>Commission</th><th>Subscribers</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach ($plans as $p)
                <tr>
                    <td><strong>{{ $p->name }}</strong> @if($p->is_free)<span class="badge bg-secondary">Free</span>@endif @if($p->is_featured)<span class="badge bg-warning text-dark">Featured</span>@endif
                        @if($p->branch)<br><span class="badge bg-dark">Custom: {{ $p->branch->display_name }}</span>@endif<br><span class="text-muted">{{ $p->description }}</span></td>
                    <td>৳ {{ number_format($p->price_monthly) }}</td>
                    <td>৳ {{ number_format($p->price_yearly) }}</td>
                    <td>{{ $p->price_lifetime !== null ? '৳ '.number_format($p->price_lifetime) : '—' }}</td>
                    <td>{{ $p->trial_days ? $p->trial_days.' days' : '—' }}</td>
                    <td>{{ \App\Models\Plan::limitLabel($p->limit('max_products')) }} / {{ \App\Models\Plan::limitLabel($p->limit('max_staff')) }} / {{ \App\Models\Plan::limitLabel($p->limit('max_orders_month')) }}</td>
                    <td>{{ $p->commission_percent }}%</td>
                    <td>{{ $p->active_count }}</td>
                    <td><span class="badge bg-{{ $p->is_active ? 'success' : 'secondary' }}">{{ $p->is_active ? 'Active' : 'Hidden' }}</span></td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.plans.edit', $p) }}" class="btn btn-sm btn-light"><i class="fa-solid fa-pen"></i></a>
                        <form method="POST" action="{{ route('admin.plans.destroy', $p) }}" class="d-inline" onsubmit="return confirm('Delete plan?')">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="fa-solid fa-trash"></i></button></form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
