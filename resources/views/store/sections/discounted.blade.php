@include('store._product-row', ['title' => $sec['title'] ?: __('Discount Products'), 'items' => $discounted, 'link' => route('store.products', [$store, 'sale' => 1])])
