@php
    $primary = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $theme['primary']) ? $theme['primary'] : '#e41e26';
    $font = array_key_exists($theme['font'], \App\Support\StoreTheme::FONTS) ? $theme['font'] : 'Poppins';
    $radius = max(0, min(24, (int) $theme['radius']));
@endphp
@if (! in_array($font, ['Poppins', 'Hind Siliguri'], true))
<link href="https://fonts.googleapis.com/css2?family={{ urlencode($font) }}:wght@400;500;600;700&display=swap" rel="stylesheet">
@endif
<style>
:root{--brand:{{ $primary }};--brand-dark:{{ \App\Support\StoreTheme::shade($primary) }};--brand-soft:{{ \App\Support\StoreTheme::tint($primary) }};--radius:{{ $radius }}px}
/* Keep Bengali glyphs readable even when a store theme is configured with Poppins. */
body{font-family:'Hind Siliguri','{{ $font }}',system-ui,sans-serif}
.page-content img{max-width:100%;height:auto}.page-content iframe{max-width:100%;aspect-ratio:16/9;width:100%;height:auto}.ql-align-center{text-align:center}.ql-align-right{text-align:right}.ql-align-justify{text-align:justify}
</style>
