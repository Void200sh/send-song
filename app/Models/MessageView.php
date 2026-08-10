<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageView extends Model
{
    // ─── $fillable ───
    // Kolom yang boleh diisi lewat create() / fill()
    protected $fillable = [
        'message_id',   // int — pesan yang dilihat
        'ip_address',   // string/null — IP pengunjung yang melihat
    ];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }
}
