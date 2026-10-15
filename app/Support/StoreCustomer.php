<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Customer;

/** The logged-in storefront customer — only valid for the store (tenant) they registered with. */
class StoreCustomer
{
    public static function current(?Branch $branch): ?Customer
    {
        $customer = auth('customer')->user();

        return $customer && $branch && $customer->branch_id === $branch->id && $customer->is_active ? $customer : null;
    }
}
