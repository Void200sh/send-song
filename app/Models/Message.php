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
        'ip_address',
        'sender_key',
        'spam_identity_key',
        'spam_fingerprint',
        'is_spam',
        'spam_reason',
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
        'is_pinned',        // bool — pesan di-pin admin (tampil paling atas di feed)
        'pinned_at',        // datetime/null — waktu di-pin (buat urutan antar pin)
        'views',            // int — total berapa kali pesan ini dilihat
        'unique_views',     // int — berapa pengunjung unik (per IP) yang melihat pesan ini
    ];
    // ─── KOLOM LAIN YANG GAK PERLU DIISI LANGSUNG ───
    // id → auto increment (primary key)
    // created_at → diisi otomatis sama Laravel
    // updated_at → diisi otomatis sama Laravel

    // ─── EMOJI REAKSI (ala WhatsApp) ───
    // Set emoji yang bisa dipakai pengunjung untuk bereaksi ke pesan.
    public const REACTION_EMOJIS = ['👍', '❤️'];

    // ─── REAKSI ───
    // Satu pesan punya banyak reaksi (dari banyak pengunjung).
    public function reactions()
    {
        return $this->hasMany(MessageReaction::class);
    }

    // ─── BALASAN ───
    // Satu pesan punya banyak balasan (thread mini ala komentar).
    public function replies()
    {
        return $this->hasMany(MessageReply::class);
    }

    // ─── LAPORAN ───
    // Satu pesan bisa dilaporkan berkali-kali oleh pengunjung (konten tidak pantas).
    public function reports()
    {
        return $this->hasMany(MessageReport::class);
    }

    // ─── VIEWS ───
    // Satu pesan bisa dilihat banyak pengunjung (unik per IP).
    public function views()
    {
        return $this->hasMany(MessageView::class);
    }

    // Hitung jumlah reaksi per emoji, misal ['👍' => 3, '❤️' => 1].
    // Butuh relasi reactions() sudah di-load (eager loading) biar gak N+1 query.
    public function reactionCounts(): array
    {
        return $this->reactions->countBy('emoji')->toArray();
    }

    // ─── TEMA KARTU ───
    // Key tema valid untuk kartu pesan (gaya chat TikTok: gradasi pastel + dekorasi emoji).
    // 'classic' = polos (default) — disimpan sebagai NULL di DB biar konsisten sama pesan lama.
    public const THEMES = ['classic', 'bunga', 'senja', 'laut', 'lavender', 'mint', 'neon', 'film', 'pastel'];

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

    // ─── SORTIR FEED: PESAN TER-PIN PALING ATAS ───
    // is_pinned DESC (pin di atas), lalu pinned_at DESC (pin terbaru di atas),
    // lalu created_at DESC (pesan baru di atas) untuk sisanya.
    public function scopePinnedFirst($query)
    {
        return $query
            ->orderByDesc('is_pinned')
            ->orderByDesc('pinned_at')
            ->latest();
    }
}
