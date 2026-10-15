<div class="panel">
    <div class="panel-head"><span>Session #{{ $session->id }} · {{ $session->warehouse?->name }}</span>
        <span class="badge bg-{{ $session->closed_at ? 'secondary' : 'success' }}">{{ $session->closed_at ? 'Closed' : 'Open' }}</span></div>
    <div class="panel-body small">
        <div class="row mb-2">
            <div class="col">Opened: {{ $session->opened_at->format('d M Y h:i A') }}<br>by {{ $session->opened_by }}</div>
            <div class="col text-end">@if ($session->closed_at)Closed: {{ $session->closed_at->format('d M Y h:i A') }}<br>by {{ $session->closed_by }}@endif</div>
        </div>
        <table class="table table-sm mb-0">
            <tr><td>Sales (invoices)</td><td class="text-end">{{ $totals['sales_count'] ?? 0 }}</td></tr>
            <tr><td>Sales total</td><td class="text-end fw-semibold">@money($totals['sales_total'] ?? 0)</td></tr>
            @foreach (($totals['by_method'] ?? []) as $m => $amt)
                <tr><td class="ps-4">— {{ \App\Models\Order::METHOD_LABELS[$m] ?? ucfirst($m) }}</td><td class="text-end">@money($amt)</td></tr>
            @endforeach
            <tr><td>Refunds</td><td class="text-end text-danger">@money($totals['refunds'] ?? 0)</td></tr>
            <tr><td>Opening cash</td><td class="text-end">@money($session->opening_cash)</td></tr>
            <tr><td>Cash in</td><td class="text-end">@money($totals['cash_in'] ?? 0)</td></tr>
            <tr><td>Cash out (incl. cash refunds)</td><td class="text-end">− @money($totals['cash_out'] ?? 0)</td></tr>
            <tr class="fw-bold"><td>Expected cash</td><td class="text-end">@money($totals['expected_cash'] ?? 0)</td></tr>
            @if ($session->closed_at)
                <tr class="fw-bold"><td>Counted cash</td><td class="text-end">@money($session->counted_cash)</td></tr>
                <tr class="fw-bold {{ (float) $session->difference == 0 ? 'text-success' : 'text-danger' }}"><td>Difference</td><td class="text-end">{{ number_format($session->difference, 2) }}</td></tr>
            @endif
        </table>
        @if ($session->cashMovements->isNotEmpty())
            <div class="fw-semibold mt-3 mb-1">Cash movements</div>
            <table class="table table-sm mb-0">@foreach ($session->cashMovements as $cm)<tr><td>{{ $cm->created_at->format('h:i A') }}</td><td>{{ strtoupper($cm->type) }}</td><td>{{ $cm->note }} <span class="text-muted">{{ $cm->actor_name }}</span></td><td class="text-end">{{ number_format($cm->amount, 2) }}</td></tr>@endforeach</table>
        @endif
        @if ($session->note)<p class="mt-2 mb-0">Note: {{ $session->note }}</p>@endif
    </div>
</div>
