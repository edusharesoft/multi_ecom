{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($branches as $b)
    <sitemap><loc>{{ route('store.sitemap', $b) }}</loc><lastmod>{{ $b->updated_at?->toAtomString() }}</lastmod></sitemap>
@endforeach
</sitemapindex>
