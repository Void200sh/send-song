<?php

namespace App\Support;

/**
 * Utility untuk mengecilkan emoji di dalam teks user.
 *
 * Emoji dirender seukuran font induknya. Di teks besar (misal kartu story 64px),
 * emoji jadi jauh lebih dominan dari huruf — di sini setiap "cluster emoji"
 * (termasuk ZWJ keluarga 👨👩👧, skin tone 👍🏽, dan variasi FE0F ✨️)
 * dibungkus <span style="font-size:.65em"> supaya tampil 65% dari ukuran teks.
 *
 * INPUT selalu di-escape dulu (e()) — output siap dipakai dengan {!! !!} tanpa
 * risiko XSS. Regex pakai \p{Extended_Pictographic} (PCRE2/U), aman di PHP 8.2.
 */
class EmojiText
{
    /** Skala emoji terhadap font induk (0.65 = 65%). */
    public const SCALE = '.65em';

    /**
     * Escape teks user lalu bungkus tiap cluster emoji dengan span yang lebih kecil.
     */
    public static function small(string $text): string
    {
        $escaped = e($text);

        return preg_replace(
            '/\p{Extended_Pictographic}(?:\x{FE0F}\x{200D}\p{Extended_Pictographic}|\x{FE0F}|[\x{1F3FB}-\x{1F3FF}]|\x{200D}\p{Extended_Pictographic})*/u',
            '<span style="font-size:' . self::SCALE . '">$0</span>',
            $escaped
        ) ?? $escaped;
    }
}