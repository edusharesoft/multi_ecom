<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head><meta charset="utf-8"><title>Payslip {{ $item->employee?->name }} {{ $item->payroll->month }}</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{padding:24px;font-size:14px}@media print{.no-print{display:none}}</style></head>
<body onload="window.print()">
<div class="container" style="max-width:720px">
    <div class="d-flex justify-content-between border-bottom pb-2 mb-3">
        <div><h4 class="mb-0">{{ $b->display_name }}</h4><div class="text-muted small">{{ $b->address }} · {{ $b->store_phone ?: $b->mobile }}</div></div>
        <div class="text-end"><h5 class="mb-0">PAYSLIP</h5><div>{{ $item->payroll->label() }}</div></div>
    </div>
    <table class="table table-sm mb-3">
        <tr><th style="width:35%">Employee</th><td>{{ $item->employee?->name }} @if($item->employee?->code)(#{{ $item->employee->code }})@endif</td></tr>
        <tr><th>Designation</th><td>{{ $item->employee?->designation }} {{ $item->employee?->department ? '— '.$item->employee->department : '' }}</td></tr>
        <tr><th>Attendance</th><td>Present {{ $item->present_days }} · Absent {{ $item->absent_days }} · Unpaid leave {{ $item->unpaid_leave_days }} · OT {{ (float) $item->overtime_hours }}h</td></tr>
    </table>
    <div class="row">
        <div class="col-6"><table class="table table-sm table-bordered"><thead class="table-light"><tr><th colspan="2">Earnings</th></tr></thead>
            <tr><td>Basic</td><td class="text-end">{{ number_format($item->basic, 2) }}</td></tr>
            <tr><td>Allowance</td><td class="text-end">{{ number_format($item->allowance, 2) }}</td></tr>
            <tr><td>Overtime</td><td class="text-end">{{ number_format($item->overtime_amount, 2) }}</td></tr>
            <tr><td>Bonus</td><td class="text-end">{{ number_format($item->bonus, 2) }}</td></tr></table></div>
        <div class="col-6"><table class="table table-sm table-bordered"><thead class="table-light"><tr><th colspan="2">Deductions</th></tr></thead>
            <tr><td>Absence / unpaid leave</td><td class="text-end">{{ number_format($item->deduction, 2) }}</td></tr>
            <tr><td>Other (advance…)</td><td class="text-end">{{ number_format($item->other_deduction, 2) }}</td></tr></table></div>
    </div>
    <div class="d-flex justify-content-between fs-5 fw-bold border-top pt-2"><span>Net pay</span><span>৳ {{ number_format($item->net, 2) }}</span></div>
    <div class="small text-muted mt-1">{{ $item->paid_at ? 'Paid on '.$item->paid_at->format('d M Y').($item->voucher_number ? ' · Voucher '.$item->voucher_number : '') : 'Not paid yet' }} @if($item->note)· {{ $item->note }}@endif</div>
    <div class="d-flex justify-content-between mt-5 pt-4 small"><span class="border-top pt-1 px-4">Employee signature</span><span class="border-top pt-1 px-4">Authorized signature</span></div>
    <button onclick="window.print()" class="btn btn-dark btn-sm mt-4 no-print">Print</button>
</div>
</body></html>
