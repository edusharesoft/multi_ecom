<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['stock_transfer_id', 'product_id', 'variant_id', 'name', 'quantity'];
}
