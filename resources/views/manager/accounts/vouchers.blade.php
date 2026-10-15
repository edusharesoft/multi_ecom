@extends('layouts.manager')
@section('title', $kind === 'expense' ? 'Expenses' : 'Other Income')

@section('content')
@include('manager.accounts._nav')
<div class="row g-4">
    @perm('accounts.add')
    <div class="col-xl-4">
        <form method="POST" action="{{ route('manager.vouchers.store') }}" enctype="multipart/form-data" class="panel">@csrf
            <input type="hidden" name="kind" value="{{ $kind }}">
            <div class="panel-head">{{ $kind === 'expense' ? 'নতুন খরচ (Expense)' : 'নতুন আয় (Income)' }}</div>
            <div class="panel-body small">
                <label class="form-label mb-0">Category *</label>
                <select name="account_category_id" class="form-select form-select-sm mb-2" required>@foreach ($categories->where('is_active', true) as $c)<option value="{{ $c->id }}" @selected(old('account_category_id') == $c->id)>{{ $c->name }}</option>@endforeach</select>
                <div class="d-flex gap-2 mb-2">
                    <div class="flex-fill"><label class="form-label mb-0">Amount ৳ *</label><input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" class="form-control form-control-sm" required></div>
                    <div class="flex-fill"><label class="form-label mb-0">Date *</label><input type="date" name="voucher_date" value="{{ old('voucher_date', now()->toDateString()) }}" class="form-control form-control-sm" required></div>
                </div>
                <label class="form-label mb-0">{{ $kind === 'expense' ? 'Paid from' : 'Received into' }} *</label>
                <select name="account_id" class="form-select form-select-sm mb-2" required>@foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select>
                <input name="party" value="{{ old('party') }}" class="form-control form-control-sm mb-2" placeholder="{{ $kind === 'expense' ? 'Paid to (e.g. landlord, DESCO)' : 'Received from' }}">
                <input name="reference" value="{{ old('reference') }}" class="form-control form-control-sm mb-2" placeholder="Bill / receipt no.">
                <textarea name="note" rows="2" class="form-control form-control-sm mb-2" placeholder="Note">{{ old('note') }}</textarea>
                <label class="form-label mb-0">Attachment (bill photo / PDF)</label>
                <input type="file" name="attachment" accept="image/*,application/pdf" class="form-control form-control-sm mb-3">
                <button class="btn btn-brand btn-sm w-100">Save</button>
            </div>
        </form>
    </div>
    @endperm
    <div class="col-xl-8">
        <form class="panel mb-3" method="GET"><div class="panel-body d-flex flex-wrap gap-2 small">
            <input type="hidden" name="kind" value="{{ $kind }}">
            <input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control form-control-sm" style="width:140px">
            <input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control form-control-sm" style="width:140px">
            <select name="category" class="form-select form-select-sm" style="width:160px"><option value="">All categories</option>@foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>@endforeach</select>
            <select name="account" class="form-select form-select-sm" style="width:130px"><option value="">All accounts</option>@foreach ($accounts as $a)<option value="{{ $a->id }}" @selected(request('account') == $a->id)>{{ $a->name }}</option>@endforeach</select>
            <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="width:140px" placeholder="Search">
            <button class="btn btn-sm btn-dark">Filter</button>
            @perm('reports.export')<a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-file-csv"></i> CSV</a>@endperm
        </div></form>

        <div class="panel mb-3"><div class="panel-body small">
            <div class="d-flex justify-content-between mb-2"><strong>মোট {{ $kind === 'expense' ? 'খরচ' : 'আয়' }} ({{ $from->format('d M') }} – {{ $to->format('d M Y') }})</strong><strong class="fs-5">@money($total)</strong></div>
            @foreach ($categories as $c)
                @if (($byCategory[$c->id] ?? 0) > 0)
                    @php $pct = $total > 0 ? $byCategory[$c->id] / $total * 100 : 0; @endphp
                    <div class="d-flex align-items-center gap-2 mb-1"><span style="width:160px">{{ $c->name }}</span>
                        <div class="progress flex-fill" style="height:8px"><div class="progress-bar bg-{{ $kind === 'expense' ? 'danger' : 'success' }}" style="width:{{ $pct }}%"></div></div>
                        <span style="width:110px" class="text-end">৳ {{ number_format($byCategory[$c->id], 2) }}</span></div>
                @endif
            @endforeach
        </div></div>

        <div class="panel">
            <div class="table-responsive"><table class="table table-hover mb-0 small align-middle">
                <thead class="table-light"><tr><th>Voucher</th><th>Category</th><th>Party / Note</th><th>Account</th><th class="text-end">Amount</th><th></th></tr></thead>
                @forelse ($vouchers as $v)
                    <tr>
                        <td><strong>{{ $v->number }}</strong><br><span class="text-muted">{{ $v->voucher_date->format('d M Y') }}</span></td>
                        <td>{{ $v->category?->name }}</td>
                        <td>{{ $v->party }} @if($v->reference)<span class="text-muted">#{{ $v->reference }}</span>@endif<br><span class="text-muted">{{ \Illuminate\Support\Str::limit($v->note, 60) }}</span></td>
                        <td>{{ $v->account?->name }}</td>
                        <td class="text-end fw-semibold">{{ number_format($v->amount, 2) }}</td>
                        <td class="text-end text-nowrap">
                            @if ($v->attachment)<a href="{{ asset('storage/'.$v->attachment) }}" target="_blank" class="btn btn-sm btn-light" title="Attachment"><i class="fa-solid fa-paperclip"></i></a>@endif
                            @perm('accounts.delete')<form method="POST" action="{{ route('manager.vouchers.destroy', $v) }}" class="d-inline" onsubmit="return confirm('মুছে ফেলবেন?')">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="fa-solid fa-trash"></i></button></form>@endperm
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">এই সময়ে কোনো এন্ট্রি নেই।</td></tr>
                @endforelse
            </table></div>
            <div class="panel-body">{{ $vouchers->links() }}</div>
        </div>
    </div>
</div>
@endsection
