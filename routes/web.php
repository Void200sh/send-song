<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SongController;
use App\Models\Message;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| RUTE HALAMAN UTAMA (LANDING PAGE)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    // Total pesan yang bukan spam
    $totalMessages = Message::where('is_spam', false)->count();

    // Total kelas unik
    $totalKelas = Message::where('is_spam', false)
        ->distinct('kelas')
        ->count('kelas');

    // Pesan terbaru
    $latestMessage = Message::where('is_spam', false)
        ->latest()
        ->first();

    // 20 pesan acak untuk marquee
    $marqueeMessages = Message::where('is_spam', false)
        ->inRandomOrder()
        ->limit(20)
        ->get();

    return view('welcome', compact(
        'totalMessages',
        'totalKelas',
        'latestMessage',
        'marqueeMessages'
    ));
});


<<<<<<< HEAD
/*
|--------------------------------------------------------------------------
| STORAGE FILES
|--------------------------------------------------------------------------
|
| Menampilkan file dari:
|
| storage/app/public/
|
| Contoh URL:
|
| /storage/stickers/contoh.jpg
|
| File sebenarnya:
|
| storage/app/public/stickers/contoh.jpg
|
| Route ini digunakan sebagai fallback jika Apache/cPanel
| tidak dapat melayani symlink public/storage secara langsung.
|
|--------------------------------------------------------------------------
*/

Route::get('/storage/{path}', function (string $path) {

    // Hilangkan slash di awal
    $path = ltrim($path, '/');

    /*
    |--------------------------------------------------------------------------
    | SECURITY
    |--------------------------------------------------------------------------
    |
    | Mencegah path traversal seperti:
    |
    | /storage/../../.env
    |
    */

    if (
        str_contains($path, '..') ||
        str_contains($path, "\0")
    ) {
        abort(404);
    }

    /*
    |--------------------------------------------------------------------------
    | Tentukan lokasi file
    |--------------------------------------------------------------------------
    */

    $file = storage_path('app/public/' . $path);

    /*
    |--------------------------------------------------------------------------
    | Pastikan file benar-benar berada di storage/app/public
    |--------------------------------------------------------------------------
    */

    $storageRoot = realpath(storage_path('app/public'));
    $realFile = realpath($file);

    if (
        $storageRoot === false ||
        $realFile === false ||
        !str_starts_with(
            $realFile,
            $storageRoot . DIRECTORY_SEPARATOR
        )
    ) {
        abort(404);
    }

    /*
    |--------------------------------------------------------------------------
    | Pastikan file ada dan bisa dibaca
    |--------------------------------------------------------------------------
    */

    if (!is_file($realFile) || !is_readable($realFile)) {
        abort(404);
    }

    /*
    |--------------------------------------------------------------------------
    | Kirim file
    |--------------------------------------------------------------------------
    */

    return response()->file($realFile);

})->where('path', '.*');


/*
|--------------------------------------------------------------------------
| RUTE HALAMAN BROWSE / FEED PESAN
|--------------------------------------------------------------------------
*/

Route::get('/messages', [MessageController::class, 'index'])
    ->name('messages.index');


/*
|--------------------------------------------------------------------------
| RUTE HALAMAN KIRIM STORY
|--------------------------------------------------------------------------
*/

=======
// ─── RUTE INFINITE SCROLL (fragment AJAX halaman browse) ───
// WAJIB didaftarkan SEBELUM /messages/{message} biar "load-more" tidak
// tertangkap route show (implicit binding akan cari message id "load-more").
Route::get('/messages/load-more', [MessageController::class, 'loadMore'])->name('messages.load-more');

// ─── RUTE HALAMAN KIRIM STORY (TERPISAH DARI INDEX) ───
>>>>>>> 227640f4d255251c0e6e880efd463aff170b2f3b
Route::get('/story', function () {
    return view('story');
})->name('story.create');


/*
|--------------------------------------------------------------------------
| RUTE DETAIL PESAN
|--------------------------------------------------------------------------
*/

Route::get('/messages/{message}', [MessageController::class, 'show'])
    ->name('messages.show');


/*
|--------------------------------------------------------------------------
| RUTE KIRIM PESAN BARU
|--------------------------------------------------------------------------
*/

Route::post('/messages', [MessageController::class, 'store'])
    ->middleware('spam-ban')
    ->name('messages.store');


/*
|--------------------------------------------------------------------------
| RUTE REAKSI PESAN
|--------------------------------------------------------------------------
*/

Route::post('/messages/{message}/react', [MessageController::class, 'react'])
    ->middleware('throttle:30,1')
    ->name('messages.react');


/*
|--------------------------------------------------------------------------
| RUTE KIRIM BALASAN
|--------------------------------------------------------------------------
*/

Route::post('/messages/{message}/replies', [MessageController::class, 'reply'])
    ->middleware([
        'spam-ban',
        'throttle:10,1',
    ])
    ->name('messages.reply');


/*
|--------------------------------------------------------------------------
| RUTE REAKSI BALASAN
|--------------------------------------------------------------------------
*/

Route::post('/replies/{reply}/react', [MessageController::class, 'reactReply'])
    ->middleware('throttle:30,1')
    ->name('replies.react');


/*
|--------------------------------------------------------------------------
| RUTE LAPOR PESAN
|--------------------------------------------------------------------------
*/

Route::post('/messages/{message}/report', [MessageController::class, 'report'])
    ->middleware('throttle:10,1')
    ->name('messages.report');


/*
|--------------------------------------------------------------------------
| RUTE SIMPAN FEEDBACK
|--------------------------------------------------------------------------
*/

Route::post('/feedback', [MessageController::class, 'feedback'])
    ->middleware('throttle:5,1')
    ->name('feedback.store');


/*
|--------------------------------------------------------------------------
| DASHBOARD BREEZE
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', function () {
    return view('dashboard');
})
    ->middleware([
        'auth',
        'verified',
    ])
    ->name('dashboard');


/*
|--------------------------------------------------------------------------
| PROFILE
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});


/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get('/', [AdminController::class, 'dashboard'])
            ->name('dashboard');


        /*
        |--------------------------------------------------------------------------
        | Messages
        |--------------------------------------------------------------------------
        */

        Route::get('/messages', [AdminController::class, 'messages'])
            ->name('messages');

        Route::delete('/messages/{message}', [AdminController::class, 'destroy'])
            ->name('messages.destroy');

        Route::post(
            '/messages/{message}/resolve-song',
            [AdminController::class, 'resolveSong']
        )->name('messages.resolve-song');

        Route::post(
            '/messages/{message}/pin-toggle',
            [AdminController::class, 'togglePin']
        )->name('messages.pin-toggle');


        /*
        |--------------------------------------------------------------------------
        | Spam
        |--------------------------------------------------------------------------
        */

        Route::get('/spam', [AdminController::class, 'spam'])
            ->name('spam');

        Route::post(
            '/spam/delete-group',
            [AdminController::class, 'destroySpamGroup']
        )->name('spam.destroy-group');


        /*
        |--------------------------------------------------------------------------
        | Audit
        |--------------------------------------------------------------------------
        */

        Route::get('/audit', [AdminController::class, 'audit'])
            ->name('audit');

        Route::post(
            '/audit/logins-read',
            [AdminController::class, 'markLoginsRead']
        )->name('audit.logins-read');

        Route::delete(
            '/audit/bans/{ban}',
            [AdminController::class, 'unban']
        )->name('audit.unban');


        /*
        |--------------------------------------------------------------------------
        | Hack Traces
        |--------------------------------------------------------------------------
        */

        Route::get('/hack', [AdminController::class, 'hackTraces'])
            ->name('hack');

        Route::post(
            '/hack/read-all',
            [AdminController::class, 'markHackAttemptsRead']
        )->name('hack.read-all');

        Route::delete(
            '/hack/{attempt}',
            [AdminController::class, 'destroyHackAttempt']
        )->name('hack.destroy');

        Route::post(
            '/hack/clear',
            [AdminController::class, 'clearHackAttempts']
        )->name('hack.clear');


        /*
        |--------------------------------------------------------------------------
        | Feedbacks
        |--------------------------------------------------------------------------
        */

        Route::get('/feedbacks', [AdminController::class, 'feedbacks'])
            ->name('feedbacks');

        Route::delete(
            '/feedbacks/{feedback}',
            [AdminController::class, 'destroyFeedback']
        )->name('feedbacks.destroy');


        /*
        |--------------------------------------------------------------------------
        | Replies
        |--------------------------------------------------------------------------
        */

        Route::get('/replies', [AdminController::class, 'replies'])
            ->name('replies');

        Route::delete(
            '/replies/{reply}',
            [AdminController::class, 'destroyReply']
        )->name('replies.destroy');


        /*
        |--------------------------------------------------------------------------
        | Reports
        |--------------------------------------------------------------------------
        */

        Route::get('/reports', [AdminController::class, 'reports'])
            ->name('reports');

        Route::post(
            '/reports/{report}/resolve',
            [AdminController::class, 'resolveReport']
        )->name('reports.resolve');

        Route::post(
            '/reports/{report}/ban-ip',
            [AdminController::class, 'banReportIp']
        )->name('reports.ban-ip');

        Route::delete(
            '/reports/{report}/delete-message',
            [AdminController::class, 'destroyReportedMessage']
        )->name('reports.delete-message');

        Route::delete(
            '/reports/{report}',
            [AdminController::class, 'destroyReport']
        )->name('reports.destroy');


        /*
        |--------------------------------------------------------------------------
        | Songs
        |--------------------------------------------------------------------------
        */

        Route::get('/songs', [AdminController::class, 'songs'])
            ->name('songs');


        /*
        |--------------------------------------------------------------------------
        | Kelas
        |--------------------------------------------------------------------------
        */

        Route::get('/kelas', [AdminController::class, 'kelas'])
            ->name('kelas');


        /*
        |--------------------------------------------------------------------------
        | Stickers
        |--------------------------------------------------------------------------
        */

        Route::post('/stickers', [AdminController::class, 'storeSticker'])
            ->name('stickers.store');

        Route::delete(
            '/stickers/{sticker}',
            [AdminController::class, 'destroySticker']
        )->name('stickers.destroy');


        /*
        |--------------------------------------------------------------------------
        | Export
        |--------------------------------------------------------------------------
        */

        Route::get('/export', [AdminController::class, 'export'])
            ->name('export');

        Route::get(
            '/export/messages.csv',
            [AdminController::class, 'exportMessagesCsv']
        )->name('export.messages');

        Route::get(
            '/export/songs.csv',
            [AdminController::class, 'exportSongsCsv']
        )->name('export.songs');

        Route::get(
            '/export/audit.csv',
            [AdminController::class, 'exportAuditCsv']
        )->name('export.audit');

        Route::get(
            '/export/logins.csv',
            [AdminController::class, 'exportLoginsCsv']
        )->name('export.logins');

        Route::get(
            '/export/hack.csv',
            [AdminController::class, 'exportHackCsv']
        )->name('export.hack');
    });


/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/

require __DIR__ . '/auth.php';


/*
|--------------------------------------------------------------------------
| API INTERNAL
|--------------------------------------------------------------------------
|
| Pencarian dan resolve lagu.
|
*/

Route::prefix('api')->group(function () {

    Route::get(
        '/songs/search',
        [SongController::class, 'search']
    )->name('api.songs.search');

    Route::post(
        '/songs/resolve',
        [SongController::class, 'resolve']
    )->name('api.songs.resolve');
});


/*
|--------------------------------------------------------------------------
| FALLBACK 404
|--------------------------------------------------------------------------
|
| Route ini HARUS berada paling bawah.
|
*/

Route::fallback(function () {
    abort(404);
});