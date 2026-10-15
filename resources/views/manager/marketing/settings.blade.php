@extends('layouts.manager')
@section('title', 'Notifications & Pixel')

@section('content')
@include('manager.marketing._nav')
<form method="POST" action="{{ route('manager.marketing.settings.save') }}">@csrf
<div class="row g-4">
    <div class="col-xl-8">
        <div class="panel">
            <div class="panel-head">অটোমেটিক নোটিফিকেশন (কাস্টমার ও ম্যানেজার)</div>
            @unless ($canSms)<div class="panel-body small text-muted py-2"><i class="fa-solid fa-lock"></i> SMS আপনার প্ল্যানে নেই — শুধু ইমেইল যাবে।</div>@endunless
            <div class="table-responsive"><table class="table mb-0 small align-middle">
                <thead class="table-light"><tr><th style="width:190px">Event</th><th class="text-center">SMS</th><th class="text-center">Email</th><th>Message text</th></tr></thead>
                @foreach ($events as $key => $e)
                    <tr>
                        <td><strong>{{ $e['label'] }}</strong><br><code class="small">{{ $key }}</code></td>
                        <td class="text-center"><input type="hidden" name="events[{{ $key }}][sms]" value="0"><input type="checkbox" class="form-check-input" name="events[{{ $key }}][sms]" value="1" @checked($e['sms'])></td>
                        <td class="text-center"><input type="hidden" name="events[{{ $key }}][email]" value="0"><input type="checkbox" class="form-check-input" name="events[{{ $key }}][email]" value="1" @checked($e['email'])></td>
                        <td><textarea name="events[{{ $key }}][text]" rows="2" class="form-control form-control-sm">{{ $e['text'] }}</textarea></td>
                    </tr>
                @endforeach
            </table></div>
            <div class="panel-body small text-muted">Placeholders: @foreach ($placeholders as $p)<code>{{ $p }}</code> @endforeach — ফাঁকা রাখলে ডিফল্ট লেখা যাবে। ম্যানেজারকে নোটিফিকেশন যায় আপনার মোবাইল/ইমেইলে ({{ $b->mobile }}, {{ $b->email }})।</div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="panel mb-3">
            <div class="panel-head">Tracking Pixel & Analytics</div>
            <div class="panel-body small">
                <label class="form-label mb-0">Facebook (Meta) Pixel ID</label>
                <input name="fb_pixel_id" value="{{ old('fb_pixel_id', $b->option('fb_pixel_id')) }}" class="form-control form-control-sm mb-2" placeholder="123456789012345">
                <label class="form-label mb-0">Google Analytics 4 ID</label>
                <input name="ga4_id" value="{{ old('ga4_id', $b->option('ga4_id')) }}" class="form-control form-control-sm mb-2" placeholder="G-XXXXXXXXXX">
                <label class="form-label mb-0">Google Tag Manager ID</label>
                <input name="gtm_id" value="{{ old('gtm_id', $b->option('gtm_id')) }}" class="form-control form-control-sm mb-2" placeholder="GTM-XXXXXXX">
                <p class="text-muted mb-0">PageView, ViewContent, AddToCart ও Purchase ইভেন্ট আপনার স্টোরে স্বয়ংক্রিয়ভাবে যাবে — Facebook Ads এর রিটার্গেটিং ও কনভার্সন ট্র্যাকিং এর জন্য।</p>
            </div>
        </div>
        <div class="panel mb-3">
            <div class="panel-head">Chat buttons (স্টোরে)</div>
            <div class="panel-body small">
                <label class="form-label mb-0">WhatsApp number</label>
                <input name="whatsapp_number" value="{{ old('whatsapp_number', $b->option('whatsapp_number')) }}" class="form-control form-control-sm mb-1" placeholder="017XXXXXXXX">
                <div class="form-check mb-2"><input type="hidden" name="whatsapp_button" value="0"><input type="checkbox" class="form-check-input" name="whatsapp_button" value="1" @checked($b->option('whatsapp_button'))><label class="form-check-label">Floating WhatsApp button দেখাও</label></div>
                <label class="form-label mb-0">Facebook Messenger link</label>
                <input name="messenger_url" value="{{ old('messenger_url', $b->option('messenger_url')) }}" class="form-control form-control-sm" placeholder="https://m.me/yourpage">
            </div>
        </div>
        <div class="panel mb-3">
            <div class="panel-head">✨ AI shopping assistant (স্টোরে চ্যাট)</div>
            <div class="panel-body small">
                @if ($b->hasFeature('ai'))
                    <div class="form-check"><input type="hidden" name="ai_chat" value="0"><input type="checkbox" class="form-check-input" name="ai_chat" value="1" @checked($b->option('ai_chat'))><label class="form-check-label">স্টোরে AI চ্যাট বাবল দেখাও</label></div>
                    <p class="text-muted mb-0 mt-1">কাস্টমারের প্রশ্নের উত্তর দেয় (পণ্য, দাম, ডেলিভারি, রিটার্ন নীতি) — আপনার পণ্য ও Pages থেকে তথ্য নিয়ে। প্রতিটি উত্তর AI কোটা থেকে গণনা হয়।</p>
                @else
                    <span class="text-muted"><i class="fa-solid fa-lock"></i> AI আপনার প্ল্যানে নেই।</span>
                @endif
            </div>
        </div>
        <button class="btn btn-brand w-100">Save settings</button>
    </div>
</div>
</form>

<form method="POST" action="{{ route('manager.marketing.test-sms') }}" class="panel mt-4" style="max-width:480px">@csrf
    <div class="panel-body small d-flex gap-2 align-items-center">
        <span>Test SMS</span><input name="to" class="form-control form-control-sm" placeholder="01XXXXXXXXX" required><button class="btn btn-sm btn-dark">Send</button>
    </div>
</form>
@endsection
