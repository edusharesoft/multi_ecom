@extends('layouts.manager')
@section('title', __('Payroll').' — '.$payroll->label())

@section('content')
@include('manager.hrm._nav')
@php $editable = \App\Support\Tenancy::can('hrm.edit'); @endphp
<form method="POST" action="{{ route('manager.hrm.payroll.update', $payroll) }}" class="panel mb-3">@csrf @method('PUT')
    <div class="panel-head">{{ $payroll->label() }} · {{ $payroll->working_days }} working days
        <span class="badge bg-{{ $payroll->status === 'paid' ? 'success' : 'warning text-dark' }}">{{ ucfirst($payroll->status) }}</span></div>
    <div class="table-responsive"><table class="table table-sm mb-0 small align-middle">
        <thead class="table-light"><tr><th>Employee</th><th class="text-center">P / A / UL</th><th class="text-end">Basic</th><th class="text-end">Allow.</th><th class="text-end">OT</th><th style="width:95px">Bonus</th><th style="width:95px">Absence ded.</th><th style="width:95px">Other ded.</th><th class="text-end">Net</th><th></th></tr></thead>
        @foreach ($payroll->items as $it)
            @php $locked = $it->paid_at || ! $editable; @endphp
            <tr class="{{ $it->paid_at ? 'table-success' : '' }}">
                <td><strong>{{ $it->employee?->name }}</strong><br><span class="text-muted">{{ $it->employee?->designation }}</span></td>
                <td class="text-center">{{ $it->present_days }} / {{ $it->absent_days }} / {{ $it->unpaid_leave_days }}</td>
                <td class="text-end">{{ number_format($it->basic, 2) }}</td>
                <td class="text-end">{{ number_format($it->allowance, 2) }}</td>
                <td class="text-end">{{ number_format($it->overtime_amount, 2) }}@if($it->overtime_hours > 0)<br><span class="text-muted">{{ (float) $it->overtime_hours }}h</span>@endif</td>
                <td><input type="number" step="0.01" min="0" name="items[{{ $it->id }}][bonus]" value="{{ (float) $it->bonus }}" class="form-control form-control-sm" @disabled($locked)></td>
                <td><input type="number" step="0.01" min="0" name="items[{{ $it->id }}][deduction]" value="{{ (float) $it->deduction }}" class="form-control form-control-sm" @disabled($locked)></td>
                <td><input type="number" step="0.01" min="0" name="items[{{ $it->id }}][other_deduction]" value="{{ (float) $it->other_deduction }}" class="form-control form-control-sm" @disabled($locked) title="Advance, fine…"></td>
                <td class="text-end fw-bold">{{ number_format($it->net, 2) }}</td>
                <td class="text-nowrap"><a href="{{ route('manager.hrm.payslip', $it) }}" target="_blank" class="btn btn-sm btn-light" title="Payslip"><i class="fa-solid fa-file-invoice"></i></a>
                    @if($it->paid_at)<span class="badge bg-success">paid</span>@endif</td>
            </tr>
        @endforeach
        <tfoot><tr class="fw-bold"><td colspan="8" class="text-end">Total</td><td class="text-end">@money($payroll->total_net)</td><td></td></tr></tfoot>
    </table></div>
    @if ($payroll->status !== 'paid' && $editable)<div class="panel-body"><button class="btn btn-sm btn-dark">Save changes</button></div>@endif
</form>

@if ($payroll->status !== 'paid')
    @perm('hrm.approve')
    <form method="POST" action="{{ route('manager.hrm.payroll.pay', $payroll) }}" class="panel" onsubmit="return confirm('সব অপরিশোধিত বেতন পরিশোধ হিসেবে চিহ্নিত করবেন?')">@csrf
        <div class="panel-body small d-flex flex-wrap gap-2 align-items-center">
            <strong>Pay salary</strong>
            @if ($accounts->isNotEmpty())
                <span>from</span>
                <select name="account_id" class="form-select form-select-sm" style="width:180px">@foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select>
                <span class="text-muted">→ Expenses এ “Salary” voucher তৈরি হবে</span>
            @else
                <span class="text-muted">(Accounting চালু থাকলে খরচ হিসেবে স্বয়ংক্রিয়ভাবে যোগ হয়)</span>
            @endif
            <button class="btn btn-sm btn-brand ms-auto"><i class="fa-solid fa-money-bill-wave"></i> Mark all as paid</button>
        </div>
    </form>
    @endperm
@endif
@endsection
