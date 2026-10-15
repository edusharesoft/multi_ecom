<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    protected $fillable = ['branch_id', 'title', 'subtitle', 'image', 'link', 'button_text', 'position', 'sort', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getImageUrlAttribute(): string
    {
        return asset('storage/'.$this->image);
    }
}
