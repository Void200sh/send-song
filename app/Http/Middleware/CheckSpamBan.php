<?php

namespace App\Http\Middleware;

use App\Services\SpamDetectionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSpamBan
{
    public function __construct(private SpamDetectionService $spam)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Konsisten dengan pencatatan IP di tempat lain (identity/reaksi):
        // pakai clientIp() yang mengutamakan X-Forwarded-For, bukan $request->ip()
        // yang bisa jadi IP proxy. Tanpa ini, ban IP manual dari laporan tidak
        // akan memblokir pengirim di belakang proxy/CDN.
        $ip = SpamDetectionService::clientIp($request);

        if ($this->spam->isBanned((string) $request->input('sender_name'), $ip)) {
            abort(403, 'Pengiriman dari identitas ini diblokir karena spam.');
        }

        return $next($request);
    }
}
