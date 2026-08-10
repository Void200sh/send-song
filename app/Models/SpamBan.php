<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpamBan extends Model
{
    protected $fillable = [
        'sender_key',
        'sender_name',
        'ip_address',
        'spam_count',
        'reason',
        'banned_at',
    ];

    protected function casts(): array
    {
        return [
            'spam_count' => 'integer',
            'banned_at' => 'datetime',
        ];
    }
}
