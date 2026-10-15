@extends('layouts.manager')
@section('title', 'Accounts')

@section('content')
@include('manager.accounts._nav')
<div class="row g-3 mb-3">
    @foreach ($accounts->where('is_active', true) as $a)
        <div class="col-6 col-lg-3">
            <a href="{{ route('manager.accounts.cashbook', ['account' => $a->id]) }}" class="stat-card text-decoration-none text-dark">
                <div class="ic" style="background:#e0f2fe;color:#0369a1"><i class="fa-solid {{ ['cash' => 'fa-money-bill', 'bank' => 'fa-building-columns', 'mobile' => 'fa-mobile-screen', 'other' => 'fa-wallet'][$a->type] ?? 'fa-wallet' }}"></i></div>
                <div><div class="v {{ $a->current < 0 ? 'text-danger' : '' }}">৳ {{ number_format($a->current, 2) }}</div><div class="l">{{ $a->name }} @if($a->is_default)<span class="badge bg-light text-dark">default</span>@endif</div></div>
            </a>
        </div>
    @endforeach
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="ic" style="background:#dcfce7;color:#15803d"><i class="fa-solid fa-sack-dollar"></i></div><div><div class="v">৳ {{ number_format($total, 2) }}</div><div class="l">মোট ব্যালেন্স</div></div></div></div>
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="panel">
            <div class="panel-head">Money accounts <span class="small text-muted fw-normal">পেমেন্ট মেথড অনুযায়ী বিক্রির টাকা স্বয়ংক্রিয়ভাবে এই অ্যাকাউন্টে জমা হয়</span></div>
            <div class="table-responsive"><table class="table mb-0 small align-middle">
                <thead class="table-light"><tr><th>Account</th><th>Receives</th><th class="text-end">Opening</th><th class="text-end">Balance</th><th></th></tr></thead>
                @foreach ($accounts as $a)
                    <tr class="{{ $a->is_active ? '' : 'text-muted' }}">
                        <td><strong>{{ $a->name }}</strong> <span class="badge bg-light text-dark">{{ \App\Models\Account::TYPES[$a->type] ?? $a->type }}</span>
                            @if($a->is_default)<span class="badge bg-success">default</span>@endif @unless($a->is_active)<span class="badge bg-secondary">inactive</span>@endunless
                            <br><span class="text-muted">{{ $a->bank_name }} {{ $a->account_number }}</span></td>
                        <td>@foreach ($a->methods ?? [] as $m)<span class="badge bg-light text-dark border me-1">{{ \App\Models\Account::METHODS[$m] ?? $m }}</span>@endforeach</td>
                        <td class="text-end">{{ number_format($a->opening_balance, 2) }}</td>
                        <td class="text-end fw-semibold {{ $a->current < 0 ? 'text-danger' : '' }}">{{ number_format($a->current, 2) }}</td>
                        <td class="text-end">@perm('accounts.edit')<button class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#acc{{ $a->id }}"><i class="fa-solid fa-pen"></i></button>@endperm</td>
                    </tr>
                    @perm('accounts.edit')
                    <tr class="collapse" id="acc{{ $a->id }}"><td colspan="5" class="bg-light">
                        @include('manager.accounts._form', ['a' => $a, 'action' => route('manager.accounts.update', $a), 'method' => 'PUT'])
                    </td></tr>
                    @endperm
                @endforeach
            </table></div>
        </div>
    </div>
    <div class="col-xl-4">
        @perm('accounts.add')
        <div class="panel mb-3"><div class="panel-head">নতুন অ্যাকাউন্ট</div><div class="panel-body">
            @include('manager.accounts._form', ['a' => null, 'action' => route('manager.accounts.store'), 'method' => 'POST'])
        </div></div>
        @endperm
        <div class="panel">
            <div class="panel-head">Expense / Income categories</div>
            <div class="panel-body small">
                @foreach (['expense' => 'Expense', 'income' => 'Income'] as $k => $label)
                    <div class="fw-semibold mb-1">{{ $label }}</div>
                    <div class="d-flex flex-wrap gap-1 mb-2">
                        @foreach ($categories->where('kind', $k) as $c)
                            @perm('accounts.edit')
                            <form method="POST" action="{{ route('manager.accounts.categories.toggle', $c) }}">@csrf<button class="badge border {{ $c->is_active ? 'bg-light text-dark' : 'bg-secondary text-white text-decoration-line-through' }}" title="Click to {{ $c->is_active ? 'disable' : 'enable' }}">{{ $c->name }}</button></form>
                            @else
                            <span class="badge bg-light text-dark border">{{ $c->name }}</span>
                            @endperm
                        @endforeach
                    </div>
                @endforeach
                @perm('accounts.edit')
                <form method="POST" action="{{ route('manager.accounts.categories.store') }}" class="d-flex gap-1">@csrf
                    <select name="kind" class="form-select form-select-sm" style="width:100px"><option value="expense">Expense</option><option value="income">Income</option></select>
                    <input name="name" class="form-control form-control-sm" placeholder="New category" required>
                    <button class="btn btn-sm btn-dark">Add</button>
                </form>
                @endperm
            </div>
        </div>
    </div>
</div>
@endsection
