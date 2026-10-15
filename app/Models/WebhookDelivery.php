<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookDelivery extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['branch_id', 'event', 'url', 'status_code', 'success', 'response', 'duration_ms'];

    protected function casts(): array
    {
        return ['success' => 'boolean'];
    }
}
