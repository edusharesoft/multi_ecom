@extends('layouts.main')
@section('title', 'Contact Us')
@section('content')

@php
    $contact = \App\Support\SiteFooter::platform();
    $contactEmail = $contact['email'] ?: config('mail.from.address');
    $whatsapp = $contact['socials']['whatsapp'] ?? null;
    if (! $whatsapp && $contact['phone']) {
        $whatsappDigits = preg_replace('/\D/', '', $contact['phone']);
        if (str_starts_with($whatsappDigits, '0')) {
            $whatsappDigits = '88'.$whatsappDigits;
        }
        $whatsapp = 'https://wa.me/'.$whatsappDigits;
    }
    $mapQuery = $contact['address'] ?: $contact['name'].', Bangladesh';
@endphp

<style>
    .contact-page{--contact-ink:#17212b;--contact-muted:#64727d;--contact-line:#e4e9ed;background:#f5f7f8;padding:44px 0 56px;color:var(--contact-ink)}
    .contact-heading{max-width:650px;margin:0 auto 28px;text-align:center}
    .contact-kicker{display:inline-flex;align-items:center;gap:8px;color:var(--brand);font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1px}
    .contact-heading h1{font-size:clamp(28px,4vw,40px);font-weight:700;margin:10px 0 8px}
    .contact-heading p{color:var(--contact-muted);margin:0}
    .contact-info{height:100%;min-height:112px;background:#fff;border:1px solid var(--contact-line);border-radius:8px;padding:17px;display:flex;gap:13px;align-items:flex-start}
    .contact-info-icon{flex:0 0 40px;width:40px;height:40px;border-radius:8px;background:var(--brand-soft);color:var(--brand);display:flex;align-items:center;justify-content:center;font-size:16px}
    .contact-info-label{color:var(--contact-muted);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;margin:1px 0 5px}
    .contact-info-value{font-weight:600;line-height:1.5;overflow-wrap:anywhere}
    .contact-info-value a{color:inherit;text-decoration:none}
    .contact-info-value a:hover{color:var(--brand)}
    .contact-panel{height:100%;background:#fff;border:1px solid var(--contact-line);border-radius:8px;overflow:hidden}
    .contact-form-panel{padding:28px}
    .contact-panel-title{font-size:20px;font-weight:700;margin:0 0 5px}
    .contact-panel-subtitle{color:var(--contact-muted);font-size:13px;margin-bottom:22px}
    .contact-form-panel .form-label{font-size:12px;font-weight:600;margin-bottom:6px}
    .contact-form-panel .form-control{border-color:#dce2e7;border-radius:6px;padding:11px 12px;font-size:13px}
    .contact-form-panel .form-control:focus{border-color:var(--brand);box-shadow:0 0 0 3px rgba(228,30,38,.1)}
    .contact-map{min-height:450px;display:flex;flex-direction:column}
    .contact-map-head{padding:22px 24px 16px}
    .contact-map-frame{flex:1;min-height:340px;border:0;width:100%;background:#e9eef0}
    .contact-map-link{display:flex;justify-content:space-between;align-items:center;gap:10px;border-top:1px solid var(--contact-line);padding:13px 20px;color:var(--brand);font-size:12px;font-weight:600;text-decoration:none}
    .contact-map-link:hover{background:#fafbfc;color:var(--brand-dark)}
    @media(max-width:767.98px){.contact-page{padding:32px 0 42px}.contact-form-panel{padding:22px}.contact-map{min-height:400px}.contact-map-frame{min-height:300px}}
</style>

<main class="contact-page">
    <div class="container">
        <header class="contact-heading">
            <span class="contact-kicker"><i class="fa-regular fa-paper-plane"></i> We are here to help</span>
            <h1>Get in touch</h1>
            <p>Questions, feedback, or need a hand? Send us a message or reach us through any of the channels below.</p>
        </header>

        <section class="row g-3 mb-4" aria-label="Contact information">
            @if ($contact['phone'])
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="contact-info">
                        <span class="contact-info-icon"><i class="fa-solid fa-phone"></i></span>
                        <div><div class="contact-info-label">Call us</div><div class="contact-info-value"><a href="tel:{{ preg_replace('/[^\d+]/', '', $contact['phone']) }}">{{ $contact['phone'] }}</a></div></div>
                    </div>
                </div>
            @endif
            @if ($whatsapp)
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="contact-info">
                        <span class="contact-info-icon"><i class="fa-brands fa-whatsapp"></i></span>
                        <div><div class="contact-info-label">WhatsApp</div><div class="contact-info-value"><a href="{{ $whatsapp }}" target="_blank" rel="noopener">Chat with us <i class="fa-solid fa-arrow-up-right-from-square small ms-1"></i></a></div></div>
                    </div>
                </div>
            @endif
            @if ($contactEmail)
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="contact-info">
                        <span class="contact-info-icon"><i class="fa-regular fa-envelope"></i></span>
                        <div><div class="contact-info-label">Email us</div><div class="contact-info-value"><a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a></div></div>
                    </div>
                </div>
            @endif
            @if ($contact['address'])
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="contact-info">
                        <span class="contact-info-icon"><i class="fa-solid fa-location-dot"></i></span>
                        <div><div class="contact-info-label">Visit us</div><div class="contact-info-value">{{ $contact['address'] }}</div></div>
                    </div>
                </div>
            @endif
        </section>

        <section class="row g-3 align-items-stretch" aria-label="Send us a message or find us">
            <div class="col-lg-5">
                <div class="contact-panel contact-form-panel">
                    <h2 class="contact-panel-title">Send us a message</h2>
                    <p class="contact-panel-subtitle">Send us your question and our team will get back to you.</p>
                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">Please review the form and correct the highlighted fields.</div>
                    @endif
                    <form id="contactForm" action="{{ route('contact.submit') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="contactName" class="form-label">Your name</label>
                            <input id="contactName" name="name" class="form-control @error('name') is-invalid @enderror" type="text" autocomplete="name" placeholder="e.g. Alex Morgan" value="{{ old('name') }}" maxlength="120" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="contactSenderEmail" class="form-label">Your email</label>
                            <input id="contactSenderEmail" name="sender_email" class="form-control @error('sender_email') is-invalid @enderror" type="email" autocomplete="email" placeholder="you@example.com" value="{{ old('sender_email') }}" maxlength="150" required>
                            @error('sender_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="contactSubject" class="form-label">Subject</label>
                            <input id="contactSubject" name="subject" class="form-control @error('subject') is-invalid @enderror" type="text" placeholder="How can we help?" value="{{ old('subject') }}" maxlength="180" required>
                            @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-4">
                            <label for="contactMessage" class="form-label">Message</label>
                            <textarea id="contactMessage" name="message" class="form-control @error('message') is-invalid @enderror" rows="5" placeholder="Write your message here..." maxlength="10000" required>{{ old('message') }}</textarea>
                            @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button class="btn btn-brand px-4 py-2" type="submit"><i class="fa-regular fa-paper-plane me-2"></i>Send message</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="contact-panel contact-map">
                    <div class="contact-map-head">
                        <h2 class="contact-panel-title">Find us</h2>
                        <p class="contact-panel-subtitle mb-0">{{ $contact['address'] ?: $contact['name'].', Bangladesh' }}</p>
                    </div>
                    <iframe class="contact-map-frame" title="Map showing {{ $contact['address'] ?: $contact['name'] }}" src="https://maps.google.com/maps?q={{ urlencode($mapQuery) }}&amp;output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                    <a class="contact-map-link" href="https://www.google.com/maps/search/?api=1&amp;query={{ urlencode($mapQuery) }}" target="_blank" rel="noopener">
                        <span><i class="fa-solid fa-diamond-turn-right me-2"></i>Open directions in Google Maps</span>
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                </div>
            </div>
        </section>
    </div>
</main>

@endsection
