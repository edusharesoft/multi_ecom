<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['branch_id', 'feature', 'provider', 'model', 'input_tokens', 'output_tokens', 'success', 'error', 'duration_ms'];

    protected function casts(): array
    {
        return ['success' => 'boolean'];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
