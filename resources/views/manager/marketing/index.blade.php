@extends('layouts.manager')
@section('title', 'Marketing')

@section('content')
@include('manager.marketing._nav')
@include('manager.marketing._quota')
<div class="panel">
    <div class="panel-head">SMS / Email Campaigns
        @perm('marketing.add')<a href="{{ route('manager.marketing.create') }}" class="btn btn-sm btn-brand"><i class="fa-solid fa-plus"></i> New Campaign</a>@endperm</div>
    @unless ($canSms || $canEmail)<div class="panel-body small text-muted"><i class="fa-solid fa-lock"></i> SMS / Email marketing আপনার প্ল্যানে নেই — <a href="{{ route('manager.billing.plans') }}">আপগ্রেড</a>।</div>@endunless
    <div class="table-responsive"><table class="table table-hover mb-0 small align-middle">
        <thead class="table-light"><tr><th>Campaign</th><th>Channel</th><th>Audience</th><th>Status</th><th class="text-end">Sent / Total</th><th class="text-end">Failed</th><th>Date</th></tr></thead>
        @forelse ($campaigns as $c)
            <tr onclick="location='{{ route('manager.marketing.show', $c) }}'" style="cursor:pointer">
                <td><strong>{{ $c->name }}</strong><br><span class="text-muted">{{ \Illuminate\Support\Str::limit($c->message, 60) }}</span></td>
                <td>{{ strtoupper($c->channel) }}</td>
                <td>{{ \App\Services\Messaging\CampaignService::AUDIENCES[$c->audience] ?? $c->audience }}</td>
                <td><span class="badge bg-{{ ['draft' => 'secondary', 'sending' => 'warning text-dark', 'sent' => 'success', 'cancelled' => 'dark'][$c->status] ?? 'light' }}">{{ ucfirst($c->status) }}</span></td>
                <td class="text-end">{{ $c->sent }} / {{ $c->total }}</td>
                <td class="text-end {{ $c->failed ? 'text-danger' : '' }}">{{ $c->failed }}</td>
                <td>{{ ($c->started_at ?? $c->created_at)->format('d M Y') }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-5">এখনো কোনো ক্যাম্পেইন নেই। অফার, নতুন পণ্য বা ঈদের শুভেচ্ছা SMS পাঠাতে “New Campaign” চাপুন।</td></tr>
        @endforelse
    </table></div>
    <div class="panel-body">{{ $campaigns->links() }}</div>
</div>
@endsection
