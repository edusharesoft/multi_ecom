@php $cur = app()->getLocale(); @endphp
<div class="dropdown d-inline-block">
    <a href="#" class="text-decoration-none {{ $class ?? 'text-dark' }} dropdown-toggle small" data-bs-toggle="dropdown" title="Language"><i class="fa-solid fa-language"></i> {{ \App\Http\Middleware\SetLocale::LOCALES[$cur] ?? $cur }}</a>
    <ul class="dropdown-menu dropdown-menu-end small">
        @foreach (\App\Http\Middleware\SetLocale::LOCALES as $code => $label)
            <li><a class="dropdown-item {{ $cur === $code ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['lang' => $code]) }}">{{ $label }}</a></li>
        @endforeach
    </ul>
</div>
