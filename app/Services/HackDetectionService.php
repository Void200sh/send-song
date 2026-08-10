<?php

namespace App\Services;

use App\Models\HackAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HackDetectionService
{
    /**
     * Jendela dedup: percobaan dengan pola yang sama dari IP yang sama dalam X menit
     * digabung menjadi satu baris (kolom `count` dinaikkan) biar log tidak kebanjiran baris
     * oleh bot yang memindai berulang-ulang.
     */
    public const DEDUP_WINDOW_MINUTES = 10;

    /**
     * Daftar pola serangan. Setiap aturan: signature => [severity, reason, pola regex].
     * Makin tinggi severity, makin bahaya serangannya.
     *
     * @var array<string, array{severity: string, reason: string, patterns: string[]}>
     */
    private const RULES = [
        'sensitive-file' => [
            'severity' => 'medium',
            'reason' => 'Mencoba mengakses file sensitif server',
            'patterns' => [
                '/\.env\b/', '/\.git(\/|$)/', '/\.svn(\/|$)/', '/\.hg(\/|$)/',
                '/wp-admin/', '/wp-login/', '/phpmyadmin/', '/pma(\/|$)/', '/adminer/',
                '/config\.php/', '/configuration\.php/', '/\.htaccess/', '/laravel\.log/',
                '/xmlrpc\.php/', '/server-status/', '/server-info/', '/phpinfo/',
                '/actuator/', '/web\.config/', '/crossdomain\.xml/', '/aws\/credentials/',
                '/id_rsa/', '/\.sql(\?|$)/', '/\.bak(\?|$)/', '/\.zip(\?|$)/',
            ],
        ],
        'path-traversal' => [
            'severity' => 'high',
            'reason' => 'Mencoba path traversal (akses file di luar direktori web)',
            'patterns' => [
                '/\.\.\//', '/\.\.\\\\/', '/\.\.%2f/i', '/\.\.%5c/i',
                '/(\/etc\/passwd|\/etc\/shadow|boot\.ini|win\.ini|proc\/self\/environ|windows\\\\system32)/i',
            ],
        ],
        'sql-injection' => [
            'severity' => 'high',
            'reason' => 'Mencoba injeksi SQL',
            'patterns' => [
                '/union\s+(all\s+)?select/i', '/information_schema/i', '/sleep\s*\(/i',
                '/benchmark\s*\(/i', '/pg_sleep\s*\(/i', '/waitfor\s+delay/i',
                '/;\s*drop\s+table/i', '/;\s*delete\s+from/i', '/;\s*truncate\s+table/i',
                '/\bor\s+1\s*=\s*1\b/i', "/'\s*or\s*'/i", '/"\s*or\s*"/i',
            ],
        ],
        'xss' => [
            'severity' => 'medium',
            'reason' => 'Mencoba injeksi skrip (XSS)',
            'patterns' => [
                '/<script/i', '/<iframe/i', '/<svg[^>]*onload/i', '/javascript:/i',
                '/onerror\s*=/i', '/onload\s*=/i', '/onclick\s*=/i', '/document\.cookie/i',
                '/alert\s*\(\s*document/i',
            ],
        ],
        'command-injection' => [
            'severity' => 'critical',
            'reason' => 'Mencoba injeksi perintah sistem (command injection)',
            'patterns' => [
                '/;\s*(ls|id|whoami|cat|rm|wget|curl|nc|bash|sh|python|perl)\b/i',
                '/\|\s*(id|whoami|cat|uname|env|ls)\b/i',
                '/\$\s*\(/i', '/\$\{/i', '/`/i', '/\/bin\/(sh|bash)/i', '/%0a/i', '/%0d%0a/i',
            ],
        ],
        'code-injection' => [
            'severity' => 'critical',
            'reason' => 'Mencoba injeksi kode PHP',
            'patterns' => [
                '/eval\s*\(/i', '/assert\s*\(/i', '/base64_decode\s*\(/i', '/system\s*\(/i',
                '/shell_exec\s*\(/i', '/passthru\s*\(/i', '/create_function/i',
                '/\$_(GET|POST|REQUEST|COOKIE)\b/i',
            ],
        ],
    ];

    /**
     * ─── IP CLIENT ───
     * Pakai sumber kebenaran yang sama dengan deteksi spam (X-Forwarded-For dulu).
     */
    public static function clientIp(Request $request): string
    {
        return SpamDetectionService::clientIp($request);
    }

    /**
     * Cek apakah request mengandung pola serangan. Return deskripsi serangan atau null.
     *
     * @return array{signature: string, severity: string, reason: string}|null
     */
    public function detect(Request $request): ?array
    {
        // URL query di-decode dulu biar pola seperti <script> / ../ ketahuan walau di-encode bot
        $needle = strtolower(
            urldecode((string) $request->path()) . ' '
            . urldecode((string) $request->getQueryString()) . ' '
            . $this->bodyText($request)
        );

        $best = null;
        foreach (self::RULES as $signature => $rule) {
            foreach ($rule['patterns'] as $pattern) {
                if (! preg_match($pattern, $needle)) {
                    continue;
                }

                if ($best === null || $this->severityWeight($rule['severity']) > $this->severityWeight($best['severity'])) {
                    $best = [
                        'signature' => $signature,
                        'severity' => $rule['severity'],
                        'reason' => $rule['reason'],
                    ];
                }
                break;
            }
        }

        return $best;
    }

    /**
     * Catat request mencurigakan (dipanggil dari middleware untuk pengunjung / guest).
     */
    public function capture(Request $request): void
    {
        if (auth()->check()) {
            return; // hanya "pihak luar" (belum login) yang dipantau — aktivitas admin sendiri tidak dicatat
        }

        $detected = $this->detect($request);
        if (! $detected) {
            return;
        }

        $this->record(
            $request,
            $detected['signature'],
            $detected['severity'],
            $detected['reason'],
            Str::limit($this->bodyText($request), 2000)
        );
    }

    /**
     * Catat percobaan login gagal — ciri khas brute-force ke halaman admin.
     */
    public function logFailedLogin(Request $request, string $email): void
    {
        $this->record(
            $request,
            'failed-login',
            'low',
            'Percobaan login gagal (kemungkinan brute-force)',
            Str::limit('Login gagal untuk email: ' . $email, 500)
        );
    }

    /**
     * Simpan satu baris jejak hacking, dengan dedup 10 menit per (IP + pola + path).
     */
    private function record(Request $request, string $signature, string $severity, string $reason, ?string $payload): void
    {
        try {
            $ip = self::clientIp($request);
            $path = '/' . ltrim((string) $request->path(), '/');

            $recent = HackAttempt::query()
                ->where('ip_address', $ip)
                ->where('signature', $signature)
                ->where('path', $path)
                ->where('created_at', '>=', now()->subMinutes(self::DEDUP_WINDOW_MINUTES))
                ->latest('id')
                ->first();

            if ($recent) {
                $recent->increment('count');
                if (! $recent->is_new) {
                    $recent->update(['is_new' => true]);
                }

                return;
            }

            HackAttempt::create([
                'ip_address' => $ip,
                'method' => $request->method(),
                'path' => Str::limit($path, 500),
                'query_string' => Str::limit((string) $request->getQueryString(), 2000) ?: null,
                'payload' => $payload,
                'user_agent' => Str::limit((string) $request->userAgent(), 300) ?: null,
                'reason' => $reason,
                'signature' => $signature,
                'severity' => $severity,
                'is_new' => true,
            ]);
        } catch (\Throwable $e) {
            // Logger keamanan TIDAK BOLEH menjatuhkan aplikasi: kalau tabel belum ada,
            // DB read-only, atau SQLite terkunci, cukup laporkan ke log Laravel saja
            // dan biarkan request tetap jalan normal.
            report($e);
        }
    }

    /**
     * Isi body request (field biasa saja, tanpa token CSRF / file upload) dalam bentuk teks.
     */
    private function bodyText(Request $request): string
    {
        $data = array_filter(
            $request->except(['_token', '_method']),
            fn ($value) => $value === null || is_scalar($value)
        );

        return (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function severityWeight(string $severity): int
    {
        return match ($severity) {
            'low' => 1,
            'medium' => 2,
            'high' => 3,
            'critical' => 4,
            default => 2,
        };
    }
}
