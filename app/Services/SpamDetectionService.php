<?php

namespace App\Services;

use App\Models\Message;
use App\Models\SpamBan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SpamDetectionService
{
    public const BAN_THRESHOLD = 10;

    /**
     * ─── IP CLIENT (SATU-SATUNYA SUMBER KEBENARAN) ───
     * Prioritas header X-Forwarded-For (buat server di belakang proxy/CDN seperti Cloudflare —
     * bisa berisi daftar IP dipisah koma, ambil yang paling kiri = client asli).
     * Kalau gak ada / kosong / formatnya gak valid, fallback ke IP yang terdeteksi Laravel.
     * Dipakai di sini (identity) DAN di reaksi emoji supaya konsisten — kalau beda,
     * semua pengunjung di belakang proxy yang sama bakal dianggap 1 orang.
     */
    public static function clientIp(Request $request): string
    {
        $ip = trim((string) $request->header('X-Forwarded-For'));
        if ($ip !== '' && str_contains($ip, ',')) {
            $ip = trim(explode(',', $ip)[0]);
        }
        if ($ip === '' || ! filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = (string) $request->ip();
        }

        return $ip;
    }

    /**
     * Return request-derived identity data without storing raw name as a grouping key.
     */
    public function identity(Request $request): array
    {
        $senderKey = $this->normaliseSender($request->input('sender_name'));

        $ip = self::clientIp($request);

        return [
            'sender_key' => $senderKey,
            'spam_identity_key' => hash('sha256', $senderKey . '|' . $ip),
            'ip_address' => $ip,
            'spam_fingerprint' => $this->fingerprint($request->input('message')),
        ];
    }

    /**
     * Strict rule: both rapid volume and repeated content must be present.
     */
    public function assess(array $identity, string $message): array
    {
        $base = Message::query()
            ->where('spam_identity_key', $identity['spam_identity_key'])
            ->where('created_at', '>=', now()->subMinute());

        $rapidCount = (clone $base)->count();
        $fingerprint = $this->fingerprint($message);
        $repeatCount = (clone $base)
            ->where('spam_fingerprint', $fingerprint)
            ->count();

        $isSpam = $rapidCount >= 5 && $repeatCount >= 2;

        return [
            'is_spam' => $isSpam,
            'spam_reason' => $isSpam
                ? 'Lebih dari 5 pesan dalam 1 menit dan isi pesan berulang.'
                : null,
            'spam_fingerprint' => $fingerprint,
        ];
    }

    public function recordAndMaybeBan(Message $message): void
    {
        if (! $message->is_spam || ! $message->ip_address || ! $message->sender_key) {
            return;
        }

        DB::transaction(function () use ($message): void {
            $spamCount = Message::query()
                ->where('spam_identity_key', $message->spam_identity_key)
                ->where('is_spam', true)
                ->lockForUpdate()
                ->count();

            if ($spamCount < self::BAN_THRESHOLD) {
                return;
            }

            SpamBan::query()->updateOrCreate(
                [
                    'sender_key' => $message->sender_key,
                    'ip_address' => $message->ip_address,
                ],
                [
                    'sender_name' => $message->sender_name,
                    'spam_count' => $spamCount,
                    'reason' => '10 spam terdeteksi otomatis.',
                    'banned_at' => now(),
                ]
            );
        });
    }

    public function isBanned(string $senderName, string $ip): bool
    {
        return SpamBan::query()
            ->where('sender_key', $this->normaliseSender($senderName))
            ->where('ip_address', $ip)
            ->exists();
    }

    public function normaliseSender(?string $senderName): string
    {
        $senderName = Str::lower(trim((string) $senderName));

        return $senderName !== '' ? $senderName : 'anonymous';
    }

    public function fingerprint(?string $message): string
    {
        $normalised = Str::of((string) $message)
            ->lower()
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->toString();

        return hash('sha256', $normalised);
    }

    public function topOffenders()
    {
        return Message::query()
            ->where('is_spam', true)
            ->select(
                'sender_key',
                'ip_address',
                DB::raw('COUNT(*) as spam_total'),
                DB::raw('MAX(sender_name) as sender_name'),
            )
            ->groupBy('sender_key', 'ip_address')
            ->orderByDesc('spam_total');
    }
}
