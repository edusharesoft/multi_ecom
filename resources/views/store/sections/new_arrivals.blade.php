@include('store._product-row', ['title' => $sec['title'] ?: __('New Arrival'), 'items' => $newArrivals, 'link' => route('store.products', $store)])
