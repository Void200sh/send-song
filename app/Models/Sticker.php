<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sticker extends Model
{
    protected $fillable = [
        'name',
        'path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // URL publik stiker (via symlink storage → public/storage)
    public function url(): string
    {
        return asset('storage/' . $this->path);
    }
}
