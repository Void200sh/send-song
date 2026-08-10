<?php

namespace App\Http\Middleware;

use App\Services\HackDetectionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackHackAttempt
{
    public function __construct(private HackDetectionService $hack)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Catat percobaan mencurigakan dari pengunjung luar — tidak memblokir apa pun,
        // cuma direkam supaya admin bisa lihat jejaknya di halaman "Jejak Hacking".
        $this->hack->capture($request);

        return $next($request);
    }
}
