<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['branch_id', 'channel', 'to', 'subject', 'body', 'status', 'context', 'campaign_id', 'order_id', 'segments', 'error'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
