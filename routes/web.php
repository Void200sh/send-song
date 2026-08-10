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
});

require __DIR__.'/auth.php';

// ─── API INTERNAL: PENCARIAN & RESOLVE LAGU ───
Route::prefix('api')->group(function () {
    Route::get('/songs/search', [SongController::class, 'search'])->name('api.songs.search');
    Route::post('/songs/resolve', [SongController::class, 'resolve'])->name('api.songs.resolve');
});
