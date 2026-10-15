{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($urls as [$loc, $mod, $freq, $prio])
    <url><loc>{{ $loc }}</loc>@if($mod)<lastmod>{{ \Illuminate\Support\Carbon::parse($mod)->toAtomString() }}</lastmod>@endif<changefreq>{{ $freq }}</changefreq><priority>{{ $prio }}</priority></url>
@endforeach
</urlset>
