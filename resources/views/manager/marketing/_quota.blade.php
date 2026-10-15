<div class="alert alert-light border small d-flex flex-wrap gap-3 align-items-center">
    <span><i class="fa-solid fa-comment-sms"></i> SMS এই মাসে: <strong>{{ $smsUsed ?? 0 }}</strong> / {{ ($smsLimit ?? 0) < 0 ? 'Unlimited' : ($smsLimit ?? 0) }}</span>
    <span class="text-muted">বাংলা SMS: ৭০ অক্ষর = ১ SMS, ইংরেজি: ১৬০ অক্ষর = ১ SMS</span>
    @if (($smsLimit ?? 0) >= 0 && ($smsUsed ?? 0) >= ($smsLimit ?? 0))<a href="{{ route('manager.billing.plans') }}" class="ms-auto">আরো SMS এর জন্য আপগ্রেড করুন</a>@endif
</div>
