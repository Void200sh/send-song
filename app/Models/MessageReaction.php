<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageReaction extends Model
{
    // ─── $fillable ───
    // Kolom yang boleh diisi lewat create() / fill()
    protected $fillable = [
        'message_id',   // int — pesan yang di-reaksi
        'emoji',        // string — emoji reaksi (contoh: "👍", "❤️")
        'ip_address',   // string/null — IP pengunjung yang bereaksi
    ];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }
}
