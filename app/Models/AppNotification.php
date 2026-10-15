<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** In-app notification. branch_id null = Super Admin inbox. */
class AppNotification extends Model
{
    protected $fillable = ['branch_id', 'type', 'title', 'body', 'url', 'level', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeForAdmin($query)
    {
        return $query->whereNull('branch_id');
    }
}
