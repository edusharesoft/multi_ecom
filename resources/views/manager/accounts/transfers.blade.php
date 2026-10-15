@extends('layouts.manager')
@section('title', 'Account Transfers')

@section('content')
@include('manager.accounts._nav')
<div class="row g-4">
    @perm('accounts.add')
    <div class="col-lg-4">
        <form method="POST" action="{{ route('manager.accounts.transfers.store') }}" class="panel">@csrf
            <div class="panel-head">Transfer money</div>
            <div class="panel-body small">
                <p class="text-muted">যেমন: ক্যাশ থেকে ব্যাংকে জমা, বিকাশ থেকে ক্যাশ আউট।</p>
                <label class="form-label mb-0">From *</label>
                <select name="from_account_id" class="form-select form-select-sm mb-2">@foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }} (৳ {{ number_format($a->balance(), 2) }})</option>@endforeach</select>
                <label class="form-label mb-0">To *</label>
                <select name="to_account_id" class="form-select form-select-sm mb-2">@foreach ($accounts as $a)<option value="{{ $a->id }}" @selected($loop->index === 1)>{{ $a->name }}</option>@endforeach</select>
                <div class="d-flex gap-2 mb-2"><input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" placeholder="Amount ৳" required><input type="date" name="transfer_date" value="{{ now()->toDateString() }}" class="form-control form-control-sm" required></div>
                <input name="note" class="form-control form-control-sm mb-3" placeholder="Note (e.g. deposit slip no.)">
                <button class="btn btn-brand btn-sm w-100">Transfer</button>
            </div>
        </form>
    </div>
    @endperm
    <div class="col-lg-8">
        <div class="panel">
            <div class="table-responsive"><table class="table mb-0 small align-middle">
                <thead class="table-light"><tr><th>Date</th><th>From</th><th>To</th><th>Note</th><th class="text-end">Amount</th><th></th></tr></thead>
                @forelse ($transfers as $t)
                    <tr><td>{{ $t->transfer_date->format('d M Y') }}</td><td>{{ $t->from?->name }}</td><td>{{ $t->to?->name }}</td><td>{{ $t->note }} <span class="text-muted">{{ $t->actor_name }}</span></td>
                        <td class="text-end fw-semibold">{{ number_format($t->amount, 2) }}</td>
                        <td class="text-end">@perm('accounts.delete')<form method="POST" action="{{ route('manager.accounts.transfers.destroy', $t) }}" onsubmit="return confirm('ট্রান্সফার মুছে ফেলবেন?')">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="fa-solid fa-trash"></i></button></form>@endperm</td></tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">No transfers yet.</td></tr>
                @endforelse
            </table></div>
            <div class="panel-body">{{ $transfers->links() }}</div>
        </div>
    </div>
</div>
@endsection
