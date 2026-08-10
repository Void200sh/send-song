<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CheckSpamBan;
use App\Http\Middleware\TrackHackAttempt;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'spam-ban' => CheckSpamBan::class,
        ]);

        // Catat percobaan mencurigakan (SQLi, XSS, probing file sensitif, dll) ke tabel hack_attempts
        $middleware->web(append: [
            TrackHackAttempt::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
