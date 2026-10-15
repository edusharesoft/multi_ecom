<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    protected $fillable = ['branch_id', 'name', 'channel', 'subject', 'message', 'audience', 'custom_numbers', 'status', 'total', 'sent', 'failed', 'started_at', 'finished_at', 'actor_name'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'finished_at' => 'datetime'];
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function pending(): int
    {
        return $this->recipients()->where('status', 'pending')->count();
    }
}
