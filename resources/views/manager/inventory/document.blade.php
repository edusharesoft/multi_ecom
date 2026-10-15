@extends('layouts.manager')
@section('title', ucfirst($type).' '.$doc->number)

@section('content')
<a href="{{ route($type === 'transfer' ? 'manager.inventory.transfers' : 'manager.inventory.adjustments') }}" class="small text-muted"><i class="fa-solid fa-arrow-left"></i> Back</a>
<div class="panel mt-2" style="max-width:820px">
    <div class="panel-head">{{ $doc->number }} <button onclick="window.print()" class="btn btn-sm btn-outline-dark"><i class="fa-solid fa-print"></i></button></div>
    <div class="panel-body small">
        <div class="mb-2">{{ $doc->created_at->format('d M Y, h:i A') }} · by {{ $doc->actor_name }}</div>
        @if ($type === 'transfer')
            <div class="mb-2"><strong>{{ $doc->from?->name }}</strong> → <strong>{{ $doc->to?->name }}</strong></div>
        @else
            <div class="mb-2">{{ $doc->warehouse?->name }} · <strong>{{ \App\Models\StockAdjustment::TYPES[$doc->type] }}</strong> {{ $doc->reason ? '— '.$doc->reason : '' }}</div>
        @endif
        <table class="table table-sm">
            <thead><tr><th>Item</th><th class="text-end">Qty</th></tr></thead>
            @foreach ($doc->items as $i)<tr><td>{{ $i->name }}</td><td class="text-end">{{ $i->quantity }}</td></tr>@endforeach
        </table>
        @if ($doc->note)<p>{{ $doc->note }}</p>@endif
    </div>
</div>
@endsection
