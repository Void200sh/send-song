<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * ─── PENYIMPAN FOTO KAMERA (data URL base64) ───
 * Input: "data:image/jpeg;base64,...." (hasil jepret canvas di client).
 * Validasi isi gambar pakai getimagesizefromstring() (murni PHP, tanpa
 * ekstensi GD) — kalau bukan gambar asli, return null (abaikan).
 * Return path relatif "photos/YYYY/xxx.jpg" atau null.
 * Dipakai bersama oleh form publik (MessageController) & form admin.
 */
class PhotoStore
{
    public static function save(?string $dataUrl): ?string
    {
        // Fitur foto dinonaktifkan admin → foto diabaikan (request palsu pun aman).
        if (! Settings::photosEnabled()) {
            return null;
        }

        if (! $dataUrl || ! preg_match('#^data:image/(jpeg|png|webp);base64,(.+)$#i', $dataUrl, $m)) {
            return null;
        }

        $bytes = base64_decode($m[2], true);
        if ($bytes === false || strlen($bytes) < 20) {
            return null;
        }

        // getimagesize membaca header gambar — null untuk byte acak/bukan gambar.
        $info = @getimagesizefromstring($bytes);
        if ($info === false) {
            return null;
        }

        $ext = match ($info[2]) {
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            default => 'jpg',
        };

        $path = 'photos/' . date('Y') . '/' . date('m') . '/' . uniqid('cam_', true) . '.' . $ext;

        return Storage::disk('public')->put($path, $bytes) ? $path : null;
    }
}
