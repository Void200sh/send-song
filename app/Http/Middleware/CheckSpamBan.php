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
        if ($this->spam->isBanned((string) $request->input('sender_name'), (string) $request->ip())) {
            abort(403, 'Pengiriman dari identitas ini diblokir karena spam.');
        }

        return $next($request);
    }
}
