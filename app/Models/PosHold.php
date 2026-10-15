<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosHold extends Model
{
    protected $fillable = ['branch_id', 'label', 'cart', 'actor_name'];

    protected function casts(): array
    {
        return ['cart' => 'array'];
    }
}
