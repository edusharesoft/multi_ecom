@extends('layouts.admin')
@section('title', __('AI Settings'))

@section('content')
<div class="row g-4">
    <div class="col-xl-5">
        <form method="POST" action="{{ route('admin.ai.save') }}" class="panel mb-3">@csrf
            <div class="panel-head">AI provider</div>
            <div class="panel-body small">
                <label class="form-label mb-0">Provider</label>
                <select name="ai_provider" class="form-select form-select-sm mb-2"><option value="">— Off —</option>@foreach (\App\Services\Ai\AiService::PROVIDERS as $k => [$l, $def])<option value="{{ $k }}" @selected($provider === $k)>{{ $l }} (default: {{ $def }})</option>@endforeach</select>
                <label class="form-label mb-0">Model (খালি রাখলে default)</label>
                <input name="ai_model" value="{{ $model }}" class="form-control form-control-sm mb-2" placeholder="e.g. claude-haiku-4-5-20251001 / gpt-4o-mini / gemini-2.5-flash">
                <label class="form-label mb-0">API key @if($hasKey)<span class="text-success">✓ saved (encrypted)</span>@endif</label>
                <input type="password" name="ai_api_key" class="form-control form-control-sm mb-1" autocomplete="off" placeholder="{{ $hasKey ? 'খালি রাখলে আগেরটা থাকবে' : 'sk-...' }}">
                @if ($hasKey)<label class="mb-2"><input type="checkbox" name="remove_key" value="1" class="form-check-input"> Remove key</label>@endif
                <div class="d-flex gap-2 mt-2"><button class="btn btn-sm btn-brand">Save</button>@if($hasKey && $provider)<button formaction="{{ route('admin.ai.test') }}" class="btn btn-sm btn-outline-dark">Test connection</button>@endif</div>
                <p class="text-muted mt-2 mb-0">Key সংগ্রহ: Anthropic → console.anthropic.com · OpenAI → platform.openai.com · Gemini → aistudio.google.com। খরচ প্রোভাইডার আপনার অ্যাকাউন্ট থেকে কাটবে; ব্রাঞ্চের মাসিক লিমিট প্ল্যানের “AI requests / month” দিয়ে নিয়ন্ত্রণ করুন।</p>
            </div>
        </form>
    </div>
    <div class="col-xl-7">
        <div class="panel mb-3">
            <div class="panel-head">Usage this month</div>
            <table class="table table-sm mb-0 small">
                <thead class="table-light"><tr><th>Branch</th><th class="text-end">Requests</th><th class="text-end">OK</th><th class="text-end">Input tokens</th><th class="text-end">Output tokens</th></tr></thead>
                @forelse ($usage as $u)<tr><td>{{ $u->branch?->name ?? 'Platform (test)' }}</td><td class="text-end">{{ $u->c }}</td><td class="text-end">{{ (int) $u->ok }}</td><td class="text-end">{{ number_format($u->tin) }}</td><td class="text-end">{{ number_format($u->tout) }}</td></tr>
                @empty<tr><td colspan="5" class="text-muted text-center py-3">No AI usage yet.</td></tr>@endforelse
            </table>
        </div>
        <div class="panel">
            <div class="panel-head">Recent requests</div>
            <table class="table table-sm mb-0 small">
                @forelse ($recent as $r)<tr><td class="text-nowrap">{{ $r->created_at?->format('d M h:i A') }}</td><td>{{ $r->branch?->name ?? '—' }}</td><td><code>{{ $r->feature }}</code></td><td>{{ $r->model }}</td><td><span class="badge bg-{{ $r->success ? 'success' : 'danger' }}">{{ $r->success ? 'ok' : 'error' }}</span> <span class="text-muted">{{ \Illuminate\Support\Str::limit($r->error, 60) }}</span></td><td class="text-end text-muted">{{ $r->duration_ms }} ms</td></tr>
                @empty<tr><td class="text-muted text-center py-3">—</td></tr>@endforelse
            </table>
        </div>
    </div>
</div>
@endsection
