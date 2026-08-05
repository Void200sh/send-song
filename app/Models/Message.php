<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
// Model adalah representasi dari tabel database di Laravel (ORM Eloquent)
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    // HasFactory — biar bisa bikin data palsu (factory) buat testing, tapi gak dipake di project ini
    use HasFactory;

    // ─── $fillable ───
    // Daftar kolom yang BOLEH diisi lewat metode create() atau fill()
    // Kalo kolom gak ada di sini, bakal ditolak (mass assignment protection)
    protected $fillable = [
        'sender_name',      // string/null — nama pengirim (opsional, anonim kalo kosong)
        'recipient_name',   // string — nama penerima pesan
        'kelas',            // string — kelas penerima (ex: "XI PPLG 1")
        'message',          // text — isi pesan
        'spotify_track_id', // string/null — ID track Spotify hasil ekstraksi
        'song_title',       // string/null — judul lagu dari Spotify
        'song_artist',      // string/null — nama penyanyi
        'cover_url',        // string/null — gambar album (cover art)
        'youtube_video_id', // string/null — ID video YouTube hasil resolve
    ];
    // ─── KOLOM LAIN YANG GAK PERLU DIISI LANGSUNG ───
    // id → auto increment (primary key)
    // created_at → diisi otomatis sama Laravel
    // updated_at → diisi otomatis sama Laravel
}
