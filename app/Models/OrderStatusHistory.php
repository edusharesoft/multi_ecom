<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['order_id', 'status', 'note', 'actor_name'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
