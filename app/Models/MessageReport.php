<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageReport extends Model
{
    protected $fillable = [
        'message_id',
        'ip_address',
        'reason',
        'is_resolved',
    ];

    protected function casts(): array
    {
        return [
            'is_resolved' => 'boolean',
        ];
    }

    // Satu laporan merujuk ke satu pesan
    public function message()
    {
        return $this->belongsTo(Message::class);
    }
}
