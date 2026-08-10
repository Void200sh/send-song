<?php

namespace App\Providers;

use App\Models\HackAttempt;
use App\Models\Message;
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
        });
    }
}
