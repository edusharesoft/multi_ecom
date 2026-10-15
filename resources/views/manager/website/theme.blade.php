@extends('layouts.manager')
@section('title', 'Website — Theme')

@section('content')
@include('manager.website._nav')
<form method="POST" action="{{ route('manager.website.theme.save') }}">@csrf
<div class="row g-4">
    <div class="col-xl-7">
        <div class="panel mb-3"><div class="panel-head">Colours & font</div><div class="panel-body small">
            <label class="form-label mb-1">Brand colour</label>
            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                @foreach (\App\Support\StoreTheme::PRESETS as $hex => $label)
                    <button type="button" class="border rounded-circle p-0 preset" data-hex="{{ $hex }}" title="{{ $label }}" style="width:28px;height:28px;background:{{ $hex }}"></button>
                @endforeach
                <input type="color" name="primary" id="primary" value="{{ old('primary', $theme['primary']) }}" class="form-control form-control-color" style="width:48px">
                <code id="hexOut">{{ $theme['primary'] }}</code>
            </div>
            <div class="row g-2 mb-2">
                <div class="col-md-6"><label class="form-label mb-0">Font</label><select name="font" class="form-select form-select-sm">@foreach (\App\Support\StoreTheme::FONTS as $k => $v)<option value="{{ $k }}" @selected($theme['font'] === $k)>{{ $v }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label mb-0">Corner roundness: <span id="rOut">{{ $theme['radius'] }}</span>px</label><input type="range" name="radius" min="0" max="24" value="{{ $theme['radius'] }}" class="form-range" oninput="document.getElementById('rOut').textContent=this.value"></div>
            </div>
        </div></div>

        <div class="panel mb-3"><div class="panel-head">Header</div><div class="panel-body small">
            <label class="form-label mb-0">Top bar tagline</label>
            <input name="tagline" value="{{ old('tagline', $theme['tagline']) }}" class="form-control form-control-sm mb-2">
            <label class="form-label mb-0">Announcement bar (খালি রাখলে দেখাবে না)</label>
            <div class="d-flex gap-2 mb-2">
                <input name="announcement_text" value="{{ old('announcement_text', $theme['announcement_text']) }}" class="form-control form-control-sm" placeholder="🎉 ঈদ অফার: সব পণ্যে ২০% ছাড়!">
                <input type="color" name="announcement_bg" value="{{ $theme['announcement_bg'] ?: '#111827' }}" class="form-control form-control-color">
            </div>
            <input name="announcement_link" value="{{ old('announcement_link', $theme['announcement_link']) }}" class="form-control form-control-sm mb-2" placeholder="Link (optional) https://...">
            <label class="form-label mb-0">Store default language (কাস্টমার প্রথমবার যে ভাষা দেখবে)</label>
            <select name="default_locale" class="form-select form-select-sm mb-2" style="max-width:220px"><option value="">Platform default</option>@foreach (\App\Http\Middleware\SetLocale::LOCALES as $k => $l)<option value="{{ $k }}" @selected($b->option('default_locale') === $k)>{{ $l }}</option>@endforeach</select>
            <div class="form-check"><input type="hidden" name="show_all_branches" value="0"><input type="checkbox" class="form-check-input" name="show_all_branches" value="1" @checked($theme['show_all_branches'])><label class="form-check-label">“All Branches” লিংক দেখাও</label></div>
        </div></div>

        <div class="panel"><div class="panel-head">Extra menu links (হেডারে)</div><div class="panel-body small">
            <p class="text-muted">Pages থেকে “Show in header” দিলে পেজও মেনুতে আসবে। এখানে যেকোনো লিংক যোগ করুন (যেমন ফেসবুক গ্রুপ, একটি ক্যাটাগরি)।</p>
            <div id="menuRows">
                @foreach (array_pad($menu, max(3, count($menu) + 1), ['label' => '', 'url' => '']) as $i => $m)
                    <div class="d-flex gap-2 mb-1"><input name="menu[{{ $i }}][label]" value="{{ $m['label'] }}" class="form-control form-control-sm" placeholder="Label" style="max-width:180px"><input name="menu[{{ $i }}][url]" value="{{ $m['url'] }}" class="form-control form-control-sm" placeholder="https://... or /{{ $b->slug }}/products?sale=1"></div>
                @endforeach
            </div>
        </div></div>
    </div>
    <div class="col-xl-5">
        <div class="panel mb-3"><div class="panel-head">Footer</div><div class="panel-body small">
            <label class="form-label mb-0">About text</label>
            <textarea name="footer_about" rows="3" class="form-control form-control-sm mb-2">{{ old('footer_about', $theme['footer_about']) }}</textarea>
            @foreach (['instagram' => 'Instagram', 'youtube' => 'YouTube', 'tiktok' => 'TikTok', 'whatsapp' => 'WhatsApp (নম্বর বা লিংক)'] as $k => $l)
                <label class="form-label mb-0">{{ $l }} URL</label><input name="{{ $k }}" value="{{ old($k, $theme[$k] ?? '') }}" class="form-control form-control-sm mb-2" placeholder="https://">
            @endforeach
            <label class="form-label mb-0">Quick links (প্রতি লাইনে একটি: <code>নাম | লিংক</code>)</label>
            <textarea name="footer_links" rows="4" class="form-control form-control-sm font-monospace mb-1" placeholder="About Us | /{{ $b->slug }}/page/about&#10;Facebook Group | https://facebook.com/groups/...">{{ old('footer_links', \App\Support\SiteFooter::linksToText((array) ($theme['footer_links'] ?? []))) }}</textarea>
            <div class="form-text mb-2">Pages-এ “Show in footer” দেওয়া পেজগুলোও এখানে যোগ হবে। কিছু না দিলে All Products, Offer, Track Order, My Account দেখাবে।</div>
            <label class="form-label mb-0">Copyright text</label>
            <input name="footer_copyright" value="{{ old('footer_copyright', $theme['footer_copyright'] ?? '') }}" class="form-control form-control-sm mb-2" placeholder="© {{ date('Y') }} {{ $b->display_name }}. All rights reserved.">
            <p class="text-muted mb-2">ঠিকানা, ফোন, ইমেইল, খোলার সময়, Facebook ও লোগো আসে <a href="{{ route('manager.settings') }}">Settings</a> থেকে।</p>
            <div class="form-check"><input type="hidden" name="footer_show_tiles" value="0"><input type="checkbox" class="form-check-input" name="footer_show_tiles" value="1" id="fst" @checked($theme['footer_show_tiles'] ?? true)><label class="form-check-label" for="fst">রঙিন ৪টি বক্স দেখাও</label></div>
            <div class="form-check"><input type="hidden" name="footer_show_pay" value="0"><input type="checkbox" class="form-check-input" name="footer_show_pay" value="1" id="fsp" @checked($theme['footer_show_pay'] ?? true)><label class="form-check-label" for="fsp">পেমেন্ট লোগো দেখাও</label></div>
            <div class="form-check"><input type="hidden" name="show_newsletter" value="0"><input type="checkbox" class="form-check-input" name="show_newsletter" value="1" @checked($theme['show_newsletter'])><label class="form-check-label">Newsletter subscribe box দেখাও</label></div>
        </div></div>
        <div class="panel mb-3"><div class="panel-head">Preview</div><div class="panel-body">
            <div id="pv" class="border rounded p-3" style="--c:{{ $theme['primary'] }}">
                <div class="fw-bold mb-2" style="color:var(--c)">{{ $b->display_name }}</div>
                <button type="button" class="btn btn-sm text-white" style="background:var(--c)">SHOP NOW</button>
                <span class="badge ms-2" style="background:var(--c)">-20%</span>
            </div>
        </div></div>
        @perm('website.edit')<button class="btn btn-brand w-100">Save theme</button>@endperm
    </div>
</div>
</form>
@endsection

@push('scripts')
<script>
(() => {
    const p = document.getElementById('primary'), out = document.getElementById('hexOut'), pv = document.getElementById('pv');
    const set = v => { p.value = v; out.textContent = v; pv.style.setProperty('--c', v); };
    p.addEventListener('input', () => set(p.value));
    document.querySelectorAll('.preset').forEach(b => b.addEventListener('click', () => set(b.dataset.hex)));
})();
</script>
@endpush
