<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HackAttempt extends Model
{
    protected $fillable = [
        'ip_address',
        'method',
        'path',
        'query_string',
        'payload',
        'user_agent',
        'reason',
        'signature',
        'severity',
        'is_new',
        'count',
    ];

    protected function casts(): array
    {
        return [
            'is_new' => 'boolean',
            'count' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
