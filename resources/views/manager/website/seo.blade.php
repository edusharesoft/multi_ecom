@extends('layouts.manager')
@section('title', 'Website — SEO')

@section('content')
@include('manager.website._nav')
<div class="row g-4">
    <div class="col-xl-5">
        <form method="POST" action="{{ route('manager.website.seo.save') }}" class="panel mb-3">@csrf
            <div class="panel-head">Store SEO (Google)</div>
            <div class="panel-body small">
                <label class="form-label mb-0">Home page title <span class="text-muted">(≤60 অক্ষর)</span></label>
                <input name="title" id="sTitle" value="{{ old('title', $seo['title']) }}" maxlength="70" class="form-control form-control-sm mb-2" placeholder="{{ $b->display_name }} — Online Fashion Shop in Barishal">
                <label class="form-label mb-0">Meta description <span class="text-muted">(≤160)</span></label>
                <textarea name="description" id="sDesc" rows="3" maxlength="170" class="form-control form-control-sm mb-2">{{ old('description', $seo['description']) }}</textarea>
                <label class="form-label mb-0">Keywords</label>
                <input name="keywords" value="{{ old('keywords', $seo['keywords']) }}" class="form-control form-control-sm mb-2" placeholder="fashion, saree, panjabi, barishal">
                <label class="form-label mb-0">Google Search Console verification code</label>
                <input name="google_verification" value="{{ old('google_verification', $seo['google_verification']) }}" class="form-control form-control-sm mb-2" placeholder="content=&quot;...&quot; এর ভিতরের কোড">
                <label class="form-label mb-0">Bing verification code</label>
                <input name="bing_verification" value="{{ old('bing_verification', $seo['bing_verification']) }}" class="form-control form-control-sm mb-2">
                <div class="form-check mb-3"><input type="hidden" name="noindex" value="0"><input type="checkbox" class="form-check-input" name="noindex" value="1" @checked($seo['noindex'])><label class="form-check-label text-danger">Search engine থেকে লুকাও (noindex) — শুধু টেস্ট স্টোরের জন্য</label></div>
                <div class="border rounded p-2 mb-3 bg-white">
                    <div class="small text-muted">Google preview</div>
                    <div style="color:#1a0dab;font-size:17px" id="gTitle">{{ $seo['title'] ?: $b->display_name }}</div>
                    <div style="color:#006621;font-size:13px">{{ route('store.home', $b) }}</div>
                    <div style="color:#545454;font-size:13px" id="gDesc">{{ $seo['description'] ?: \Illuminate\Support\Str::limit($b->about ?: $b->display_name.' — online shop', 155) }}</div>
                </div>
                <button class="btn btn-brand">Save</button>
            </div>
        </form>
        <div class="panel"><div class="panel-body small">
            <div class="fw-semibold mb-1">Sitemap</div>
            <input readonly class="form-control form-control-sm mb-1" value="{{ route('store.sitemap', $b) }}" onclick="this.select()">
            <p class="text-muted mb-0">এই লিংকটি Google Search Console → Sitemaps এ জমা দিন। এতে {{ $total }} পণ্য, {{ $pagesCount }} পেজ ও {{ $postsCount }} ব্লগ পোস্ট আছে। প্রোডাক্ট পেজে Product schema (দাম, স্টক, রেটিং) স্বয়ংক্রিয়ভাবে যুক্ত হয়।</p>
        </div></div>
    </div>
    <div class="col-xl-7">
        <div class="panel">
            <div class="panel-head">SEO checker <span class="badge bg-{{ $score >= 80 ? 'success' : ($score >= 50 ? 'warning text-dark' : 'danger') }} fs-6">{{ $score }}/100</span></div>
            <div class="panel-body small">
                @foreach ([
                    'no_meta' => ['Meta description নেই', 'প্রতিটি পণ্যে ১৪০-১৬০ অক্ষরের বর্ণনা দিন — Google এ ক্লিক বাড়ে।'],
                    'no_image' => ['ছবি নেই', 'ছবি ছাড়া পণ্য Google Shopping / Facebook এ ভালো দেখায় না।'],
                    'short_desc' => ['বর্ণনা খুব ছোট (<80 অক্ষর)', 'বিস্তারিত বর্ণনা (ফেব্রিক, সাইজ, ব্যবহার) যোগ করুন।'],
                    'long_title' => ['টাইটেল ৬০ অক্ষরের বেশি', 'Google এ কেটে যায় — Meta title ছোট করুন।'],
                ] as $key => [$label, $tip])
                    @php $list = $issues[$key]; @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between"><strong>{!! $list->isEmpty() ? '<i class="fa-solid fa-circle-check text-success"></i>' : '<i class="fa-solid fa-triangle-exclamation text-warning"></i>' !!} {{ $label }}</strong><span>{{ $list->count() }} / {{ $total }}</span></div>
                        <div class="text-muted">{{ $tip }}</div>
                        @if ($list->isNotEmpty())
                            <div class="d-flex flex-wrap gap-1 mt-1">@foreach ($list->take(12) as $p)<a href="{{ route('manager.products.edit', $p->id) }}" class="badge bg-light text-dark border text-decoration-none">{{ \Illuminate\Support\Str::limit($p->name, 28) }}</a>@endforeach @if($list->count() > 12)<span class="text-muted">+{{ $list->count() - 12 }} more</span>@endif</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('sTitle').addEventListener('input', e => document.getElementById('gTitle').textContent = e.target.value || @json($b->display_name));
document.getElementById('sDesc').addEventListener('input', e => document.getElementById('gDesc').textContent = e.target.value);
</script>
@endpush
