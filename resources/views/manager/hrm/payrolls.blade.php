@extends('layouts.manager')
@section('title', __('Payroll'))

@section('content')
@include('manager.hrm._nav')
<div class="row g-4">
    <div class="col-xl-8">
        <div class="panel">
            <div class="table-responsive"><table class="table table-hover mb-0 small align-middle">
                <thead class="table-light"><tr><th>Month</th><th>Employees</th><th class="text-end">Net total</th><th>Status</th><th>Paid</th></tr></thead>
                @forelse ($payrolls as $p)
                    <tr onclick="location='{{ route('manager.hrm.payroll', $p) }}'" style="cursor:pointer">
                        <td><strong>{{ $p->label() }}</strong></td><td>{{ $p->items_count }}</td><td class="text-end">@money($p->total_net)</td>
                        <td><span class="badge bg-{{ $p->status === 'paid' ? 'success' : 'warning text-dark' }}">{{ ucfirst($p->status) }}</span></td>
                        <td>{{ $p->paid_at?->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-5">No payroll yet.</td></tr>
                @endforelse
            </table></div>
        </div>
    </div>
    @perm('hrm.edit')
    <div class="col-xl-4">
        <form method="POST" action="{{ route('manager.hrm.payrolls.generate') }}" class="panel">@csrf
            <div class="panel-head">Generate salary sheet</div>
            <div class="panel-body small">
                <label class="form-label mb-0">Month</label>
                <input type="month" name="month" value="{{ now()->subMonthNoOverflow()->format('Y-m') }}" class="form-control form-control-sm mb-2" required>
                <label class="form-label mb-0">Working days in month</label>
                <input type="number" name="working_days" value="26" min="1" max="31" class="form-control form-control-sm mb-3">
                <button class="btn btn-sm btn-brand w-100">Generate</button>
                <p class="text-muted mt-2 mb-0">হাজিরা ও Unpaid ছুটি থেকে স্বয়ংক্রিয়ভাবে কর্তন হিসাব হয়। ড্রাফট অবস্থায় আবার Generate করলে বোনাস/অন্যান্য কর্তন থেকে যায়।</p>
            </div>
        </form>
    </div>
    @endperm
</div>
@endsection
