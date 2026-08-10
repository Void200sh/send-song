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
        'theme',            // string/null — tema kartu (null = polos/classic)
        'spotify_track_id', // string/null — ID track Spotify hasil ekstraksi
        'song_title',       // string/null — judul lagu dari Spotify
        'song_artist',      // string/null — nama penyanyi
        'cover_url',        // string/null — gambar album (cover art)
        'youtube_video_id', // string/null — ID video YouTube hasil resolve
        'clip_start_seconds', // int/null — detik mulai clip lagu (null = full lagu)
        'clip_end_seconds', // int/null — detik selesai clip lagu (null = full lagu)
        'duration_seconds', // int/null — durasi asli lagu penuh (detik)
    ];
    // ─── KOLOM LAIN YANG GAK PERLU DIISI LANGSUNG ───
    // id → auto increment (primary key)
    // created_at → diisi otomatis sama Laravel
    // updated_at → diisi otomatis sama Laravel

    // ─── TEMA KARTU ───
    // Key tema valid untuk kartu pesan (gaya chat TikTok: gradasi pastel + dekorasi emoji).
    // 'classic' = polos (default) — disimpan sebagai NULL di DB biar konsisten sama pesan lama.
    public const THEMES = ['classic', 'bunga', 'senja', 'laut', 'lavender', 'mint'];

    // Durasi yang ditampilkan di kartu public: durasi klip (end-start) kalau ada klip,
    // selain itu durasi lagu penuh. Fallback "0:00" kalau keduanya belum diketahui.
    public function getDisplayDurationAttribute(): string
    {
        if ($this->clip_start_seconds !== null
            && $this->clip_end_seconds !== null
            && $this->clip_end_seconds > $this->clip_start_seconds
        ) {
            $seconds = $this->clip_end_seconds - $this->clip_start_seconds;
        } elseif ($this->duration_seconds !== null && $this->duration_seconds > 0) {
            $seconds = $this->duration_seconds;
        } else {
            return '0:00';
        }

        return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }
}
