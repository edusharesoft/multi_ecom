<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Category;

class StoreApiController extends ApiController
{
    public function show()
    {
        $b = $this->branch();

        return ['data' => [
            'id' => $b->id, 'name' => $b->display_name, 'slug' => $b->slug, 'url' => route('store.home', $b),
            'phone' => $b->store_phone ?: $b->mobile, 'email' => $b->email, 'address' => $b->address, 'currency' => 'BDT',
            'plan' => $b->effectivePlan()->name,
        ]];
    }

    public function categories()
    {
        return ['data' => Category::orderBy('sort')->get(['id', 'name', 'slug', 'parent_id'])];
    }
}
