<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageReply extends Model
{
    protected $fillable = [
        'message_id',
        'parent_id',
        'sender_name',
        'body',
        'ip_address',
        'photo_path',
        'sticker_path',
    ];

    // Satu balasan milik satu pesan
    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    // ─── REPLY KOMENTAR (1 tingkat) ───
    // parent_id null = komentar root; terisi = anak dari komentar root.
    public function parent()
    {
        return $this->belongsTo(MessageReply::class, 'parent_id');
    }

    // Anak-anak komentar ini (urut dari yang paling lama)
    public function children()
    {
        return $this->hasMany(MessageReply::class, 'parent_id')->oldest();
    }

    // Satu balasan punya banyak reaksi (dari banyak pengunjung)
    // Explicit foreign key 'reply_id' — tanpa ini Eloquent menebak 'message_reply_id'
    // dari nama model MessageReply, padahal kolom di tabel adalah reply_id.
    public function reactions()
    {
        return $this->hasMany(ReplyReaction::class, 'reply_id');
    }

    // Hitung jumlah reaksi per emoji, misal ['👍' => 3, '❤️' => 1].
    // Butuh relasi reactions() sudah di-load (eager loading) biar gak N+1 query.
    public function reactionCounts(): array
    {
        return $this->reactions->countBy('emoji')->toArray();
    }
}
