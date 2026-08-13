<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Models\Message;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SongController;

// ─── RUTE HALAMAN UTAMA (LANDING PAGE) ───
Route::get('/', function () {
    // Hitung TOTAL semua pesan di database — buat ditampilkan di stats card "stories told"
    $totalMessages = Message::where('is_spam', false)->count();
    // Hitung berapa banyak KELAS UNIK yang pernah dikirimin pesan — buat stats "classes reached"
    $totalKelas = Message::where('is_spam', false)->distinct('kelas')->count('kelas');
    // Ambil SATU pesan terbaru — buat nampilin "latest story" (waktu relative seperti "2 hours ago")
    $latestMessage = Message::where('is_spam', false)->latest()->first();

    // Ambil 20 pesan secara ACAK — buat konten marquee (teks jalan) di landing page
    $marqueeMessages = Message::where('is_spam', false)->inRandomOrder()->limit(20)->get();

    // Render file welcome.blade.php, kirim 4 data pake compact()
    return view('welcome', compact(
        'totalMessages',
        'totalKelas',
        'latestMessage',
        'marqueeMessages'
    ));
});

// ─── RUTE HALAMAN BROWSE / FEED PESAN ───
Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');

// ─── RUTE INFINITE SCROLL (fragment AJAX halaman browse) ───
// WAJIB didaftarkan SEBELUM /messages/{message} biar "load-more" tidak
// tertangkap route show (implicit binding akan cari message id "load-more").
Route::get('/messages/load-more', [MessageController::class, 'loadMore'])->name('messages.load-more');

// ─── RUTE HALAMAN KIRIM STORY (TERPISAH DARI INDEX) ───
Route::get('/story', function () {
    return view('story');
})->name('story.create');

// ─── RUTE HALAMAN DETAIL PESAN ───
// Method: GET
// URL: /messages/{id} — contoh: /messages/12
// Panggil method show() di MessageController
Route::get('/messages/{message}', [MessageController::class, 'show'])->name('messages.show');

// ─── RUTE KIRIM PESAN BARU ───
Route::post('/messages', [MessageController::class, 'store'])
    ->middleware('spam-ban')
    ->name('messages.store');

// ─── RUTE REAKSI EMOJI (toggle, ala WhatsApp) ───
// throttle: batasi 30 request/menit per IP biar jumlah reaksi gak bisa digoreng bebas.
Route::post('/messages/{message}/react', [MessageController::class, 'react'])
    ->middleware('throttle:30,1')
    ->name('messages.react');

// ─── RUTE KIRIM BALASAN (thread mini di halaman detail) ───
// spam-ban: IP yang diblokir (spam otomatis / ban manual dari laporan) tidak boleh kirim balasan.
// throttle: batasi 10 balasan/menit per IP biar gak bisa banjir spam balasan.
Route::post('/messages/{message}/replies', [MessageController::class, 'reply'])
    ->middleware(['spam-ban', 'throttle:10,1'])
    ->name('messages.reply');

// ─── RUTE REAKSI EMOJI DI BALASAN (toggle, ala WhatsApp) ───
// throttle: batasi 30 request/menit per IP biar jumlah reaksi gak bisa digoreng bebas.
Route::post('/replies/{reply}/react', [MessageController::class, 'reactReply'])
    ->middleware('throttle:30,1')
    ->name('replies.react');

// ─── RUTE LAPOR PESAN (konten tidak pantas) ───
// throttle: batasi 10 laporan/menit per IP biar laporan palsu gak bisa digoreng bebas.
Route::post('/messages/{message}/report', [MessageController::class, 'report'])
    ->middleware('throttle:10,1')
    ->name('messages.report');

// ─── RUTE SIMPAN SARAN & KRITIK (modal setelah kirim story) ───
// throttle: batasi 5 pengiriman/menit per IP biar gak bisa digoreng bebas.
Route::post('/feedback', [MessageController::class, 'feedback'])
    ->middleware('throttle:5,1')
    ->name('feedback.store');

// ─── RUTE DASHBOARD BAWAAN BREEZE ───
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ─── RUTE ADMIN (MERIDIAN / STISLA TEMPLATE) ───
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/messages', [AdminController::class, 'messages'])->name('messages');
    Route::get('/spam', [AdminController::class, 'spam'])->name('spam');
    Route::get('/audit', [AdminController::class, 'audit'])->name('audit');
    Route::post('/audit/logins-read', [AdminController::class, 'markLoginsRead'])->name('audit.logins-read');
    Route::delete('/audit/bans/{ban}', [AdminController::class, 'unban'])->name('audit.unban');
    Route::get('/hack', [AdminController::class, 'hackTraces'])->name('hack');
    Route::get('/feedbacks', [AdminController::class, 'feedbacks'])->name('feedbacks');
    Route::delete('/feedbacks/{feedback}', [AdminController::class, 'destroyFeedback'])->name('feedbacks.destroy');
    Route::get('/replies', [AdminController::class, 'replies'])->name('replies');
    Route::delete('/replies/{reply}', [AdminController::class, 'destroyReply'])->name('replies.destroy');
    Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
    Route::post('/reports/{report}/resolve', [AdminController::class, 'resolveReport'])->name('reports.resolve');
    Route::post('/reports/{report}/ban-ip', [AdminController::class, 'banReportIp'])->name('reports.ban-ip');
    Route::delete('/reports/{report}/delete-message', [AdminController::class, 'destroyReportedMessage'])->name('reports.delete-message');
    Route::delete('/reports/{report}', [AdminController::class, 'destroyReport'])->name('reports.destroy');
    Route::post('/hack/read-all', [AdminController::class, 'markHackAttemptsRead'])->name('hack.read-all');
    Route::delete('/hack/{attempt}', [AdminController::class, 'destroyHackAttempt'])->name('hack.destroy');
    Route::post('/hack/clear', [AdminController::class, 'clearHackAttempts'])->name('hack.clear');
    Route::post('/spam/delete-group', [AdminController::class, 'destroySpamGroup'])
        ->name('spam.destroy-group');
    Route::delete('/messages/{message}', [AdminController::class, 'destroy'])->name('messages.destroy');
    Route::post('/messages/{message}/resolve-song', [AdminController::class, 'resolveSong'])->name('messages.resolve-song');
    Route::post('/messages/{message}/pin-toggle', [AdminController::class, 'togglePin'])->name('messages.pin-toggle');
    Route::get('/songs', [AdminController::class, 'songs'])->name('songs');
    Route::get('/kelas', [AdminController::class, 'kelas'])->name('kelas');
    Route::get('/export', [AdminController::class, 'export'])->name('export');
    Route::get('/export/messages.csv', [AdminController::class, 'exportMessagesCsv'])->name('export.messages');
    Route::get('/export/songs.csv', [AdminController::class, 'exportSongsCsv'])->name('export.songs');
    Route::get('/export/audit.csv', [AdminController::class, 'exportAuditCsv'])->name('export.audit');
    Route::get('/export/logins.csv', [AdminController::class, 'exportLoginsCsv'])->name('export.logins');
    Route::get('/export/hack.csv', [AdminController::class, 'exportHackCsv'])->name('export.hack');
    Route::post('/stickers', [AdminController::class, 'storeSticker'])->name('stickers.store');
    Route::delete('/stickers/{sticker}', [AdminController::class, 'destroySticker'])->name('stickers.destroy');
});

require __DIR__.'/auth.php';

// ─── FALLBACK 404 ───
// Jebakan untuk semua URL yang tidak dikenal (misal /.env, /wp-admin, /phpmyadmin).
// Tanpa ini, request ke URL tersebut langsung 404 SEBELUM middleware web sempat jalan,
// jadi percobaan probing dari luar tidak akan terekam di halaman Jejak Hacking.
Route::fallback(function () {
    abort(404);
});

// ─── API INTERNAL: PENCARIAN & RESOLVE LAGU ───
Route::prefix('api')->group(function () {
    Route::get('/songs/search', [SongController::class, 'search'])->name('api.songs.search');
    Route::post('/songs/resolve', [SongController::class, 'resolve'])->name('api.songs.resolve');
});
