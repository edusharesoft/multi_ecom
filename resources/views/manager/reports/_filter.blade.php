<form class="panel mb-3 d-print-none" method="GET"><div class="panel-body d-flex flex-wrap gap-2 small align-items-center">
    <input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control form-control-sm" style="width:140px">
    <input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control form-control-sm" style="width:140px">
    @php
        $presets = [
            'Today' => [now()->toDateString(), now()->toDateString()],
            'This month' => [now()->startOfMonth()->toDateString(), now()->toDateString()],
            'Last month' => [now()->subMonthNoOverflow()->startOfMonth()->toDateString(), now()->subMonthNoOverflow()->endOfMonth()->toDateString()],
            'This year' => [now()->startOfYear()->toDateString(), now()->toDateString()],
        ];
    @endphp
    @foreach ($fields ?? [] as $name => $options)
        <select name="{{ $name }}" class="form-select form-select-sm" style="width:auto">@foreach ($options as $v => $l)<option value="{{ $v }}" @selected((string) request($name) === (string) $v)>{{ $l }}</option>@endforeach</select>
    @endforeach
    <button class="btn btn-sm btn-dark">Show</button>
    @foreach ($presets as $label => [$f, $t])<a href="{{ request()->fullUrlWithQuery(['from' => $f, 'to' => $t, 'page' => null]) }}" class="btn btn-sm btn-light border">{{ $label }}</a>@endforeach
    <span class="ms-auto"></span>
    @if (empty($noExport))
        @perm('reports.export')<a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-file-csv"></i> Excel / CSV</a>@endperm
    @endif
    @perm('reports.print')<button type="button" onclick="window.print()" class="btn btn-sm btn-outline-dark"><i class="fa-solid fa-print"></i> Print / PDF</button>@endperm
</div></form>
