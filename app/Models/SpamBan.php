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
        'ban_source', // 'auto' (deteksi spam) | 'manual' (diblokir admin)
        'banned_by',  // user_id admin yang memblokir (null = ban otomatis)
    ];

    protected function casts(): array
    {
        return [
            'spam_count' => 'integer',
            'banned_at' => 'datetime',
        ];
    }

    // Admin yang melakukan ban manual
    public function bannedBy()
    {
        return $this->belongsTo(User::class, 'banned_by');
    }
}
