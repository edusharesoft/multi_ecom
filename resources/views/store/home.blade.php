@extends('layouts.store')

@section('content')
@foreach ($sections as $sec)
    @includeIf('store.sections.'.$sec['type'], ['sec' => $sec])
@endforeach
@endsection

@push('head')
<script type="application/ld+json">{!! json_encode([
    '@@context' => 'https://schema.org', '@type' => 'Store', 'name' => $store->display_name, 'url' => route('store.home', $store),
    'telephone' => $store->store_phone ?: $store->mobile, 'address' => $store->address, 'image' => $store->logo_url ?: $store->banner_url,
    'potentialAction' => ['@type' => 'SearchAction', 'target' => route('store.products', $store).'?q={search_term_string}', 'query-input' => 'required name=search_term_string'],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush
