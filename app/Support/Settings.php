<?php

namespace App\Support;

use App\Models\Setting;

/**
 * ─── PEMBACA SETTING APLIKASI ───
 * Akses key/value dari tabel settings dengan cache per-request:
 *  - Memo static → 1 query per key per request (bukan per pesan di loop).
 *  - Cache rememberForever → setting tetap tersedia antar request tanpa
 *    query DB berulang; di-flush otomatis setiap kali set() dipanggil.
 */
class Settings
{
    /** Memo in-memory per proses (per request di web / per test). */
    protected static array $memo = [];

    /** Baca nilai setting; fallback ke $default bila key belum ada. */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, static::$memo)) {
            return static::$memo[$key];
        }

        return static::$memo[$key] = cache()->rememberForever(
            'setting.' . $key,
            fn () => Setting::query()->where('key', $key)->value('value') ?? $default
        );
    }

    /** Tulis nilai setting + invalidasi cache & memo. */
    public static function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => (string) $value]);

        cache()->forget('setting.' . $key);
        unset(static::$memo[$key]);
    }

    /**
     * Fitur foto kamera aktif/nonaktif.
     * Default: aktif (row belum ada di DB dianggap enabled).
     */
    public static function photosEnabled(): bool
    {
        return filter_var(
            static::get('photos_enabled', '1'),
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        ) ?? true;
    }

    /** Bersihkan memo — dipakai di test agar state tidak bocor antar test. */
    public static function flushMemo(): void
    {
        static::$memo = [];
    }
}
