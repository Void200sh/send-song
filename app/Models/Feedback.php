<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    // Eloquent salah menebak nama tabel jadi 'feedback' (pluralisasi tidak teratur)
    protected $table = 'feedbacks';

    protected $fillable = [
        'saran',
        'kritik',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
