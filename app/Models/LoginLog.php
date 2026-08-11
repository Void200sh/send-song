<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'status',        // 'success' | 'failed'
        'ip_address',
        'user_agent',
        'is_suspicious', // login sukses dari IP yang belum pernah dipakai user ini
        'is_new',        // belum dilihat admin (buat badge notifikasi)
    ];

    protected function casts(): array
    {
        return [
            'is_suspicious' => 'boolean',
            'is_new' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
