<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosCashMovement extends Model
{
    protected $fillable = ['pos_session_id', 'type', 'amount', 'note', 'actor_name'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }
}
