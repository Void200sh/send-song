<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReplyReaction extends Model
{
    protected $fillable = [
        'reply_id',     // int — balasan yang di-reaksi
        'emoji',        // string — emoji reaksi (contoh: "👍", "❤️")
        'ip_address',   // string/null — IP pengunjung yang bereaksi
    ];

    // Explicit foreign key 'reply_id' — tanpa ini Eloquent menebak 'message_reply_id'
    // dari nama model MessageReply.
    public function reply()
    {
        return $this->belongsTo(MessageReply::class, 'reply_id');
    }
}
