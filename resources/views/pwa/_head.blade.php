{{-- Phase 10 PWA: install metadata. Params: $manifest, $color, $appName, $icon --}}
<link rel="manifest" href="{{ $manifest }}" crossorigin="use-credentials">
<meta name="theme-color" content="{{ $color }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="{{ \Illuminate\Support\Str::limit($appName, 12, '') }}">
<link rel="apple-touch-icon" href="{{ $icon }}">
