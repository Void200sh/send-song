<?php

namespace App\Providers;

use App\Models\HackAttempt;
use App\Models\LoginLog;
use App\Models\Message;
use App\Models\MessageReport;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('admin.layouts.app', function ($view): void {
            $view->with('spamCount', Message::where('is_spam', true)->count());
            $view->with('hackCount', HackAttempt::where('is_new', true)->count());
            $view->with('reportCount', MessageReport::where('is_resolved', false)->count());
            // Login mencurigakan belum dibaca buat badge notifikasi audit security
            $view->with('loginLogCount', LoginLog::where('is_new', true)->where('is_suspicious', true)->count());
        });
    }
}
