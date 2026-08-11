<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Feedback;
use App\Models\HackAttempt;
use App\Models\LoginLog;
use App\Models\Message;
use App\Models\MessageReply;
use App\Models\MessageReport;
use App\Models\SpamBan;
use App\Models\Sticker;
use App\Services\AuditService;
use App\Services\SpamDetectionService;
use App\Services\YouTubeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    /**
     * ─── HALAMAN AUDIT SECURITY ───
     * Pusat pantauan keamanan panel admin:
     *  - Aktivitas admin (audit_logs): siapa melakukan apa kapan
     *  - Riwayat login (login_logs): sukses/gagal + login mencurigakan (IP baru)
     *  - IP ter-ban (spam_bans): daftar ban, bisa di-unban
     *  - Export log security (CSV)
     */
    public function audit(Request $request, AuditService $audit)
    {
        // Log aktivitas admin (default tampil dulu)
        $auditLogs = AuditLog::query()->latest();
        if ($request->filled('search')) {
            $s = $request->search;
            $auditLogs->where(function ($q) use ($s) {
                $q->where('user_name', 'like', '%' . $s . '%')
                    ->orWhere('action', 'like', '%' . $s . '%')
                    ->orWhere('ip_address', 'like', '%' . $s . '%');
            });
        }
        $auditLogs = $auditLogs->paginate(20, ['*'], 'audit_page')->withQueryString();

        // Riwayat login
        $loginLogs = LoginLog::query()->latest();
        if ($request->filled('login_status')) {
            $loginLogs->where('status', $request->login_status);
        }
        $loginLogs = $loginLogs->paginate(20, ['*'], 'login_page')->withQueryString();

        // IP ter-ban (SpamBan), wildcard '*' = ban seluruh IP
        $bans = SpamBan::query()
            ->with('bannedBy')
            ->orderByDesc('banned_at')
            ->paginate(20, ['*'], 'bans_page')->withQueryString();

        $stats = [
            'totalActions' => AuditLog::count(),
            'todayActions' => AuditLog::whereDate('created_at', Carbon::today())->count(),
            'totalLogins' => LoginLog::count(),
            'failedLogins' => LoginLog::where('status', 'failed')->count(),
            'suspiciousLogins' => LoginLog::where('is_suspicious', true)->count(),
            'bannedIps' => SpamBan::distinct('ip_address')->count('ip_address'),
        ];

        return view('admin.audit', compact('auditLogs', 'loginLogs', 'bans', 'stats'));
    }

    /**
     * Tandai semua log login yang belum dibaca sebagai sudah dibaca.
     */
    public function markLoginsRead()
    {
        app(AuditService::class)->log('logins.read-all', 'login_log', null, [
            'marked' => LoginLog::where('is_new', true)->count(),
        ]);
        LoginLog::where('is_new', true)->update(['is_new' => false]);

        return back()->with('success', 'Semua log login ditandai sudah dibaca.');
    }

    /**
     * ─── HAPUS BAN IP (UNBAN) ───
     * Hapus satu baris ban dari tabel spam_bans → IP bisa kirim pesan lagi.
     */
    public function unban(SpamBan $ban, AuditService $audit)
    {
        $ip = $ban->ip_address;
        $ban->delete();

        $audit->log('bans.unban', 'spam_ban', null, ['ip_address' => $ip]);

        return back()->with('success', "Ban untuk IP {$ip} berhasil dihapus — IP bisa mengirim lagi.");
    }

    /**
     * ─── DASHBOARD ADMIN — HALAMAN UTAMA ───
     * Nampilin statistik ringkas: total pesan, pesan hari ini, pengirim aktif,
     * kelas yang terjangkau, grafik 14 hari terakhir, kelas terpopuler, dan pesan terbaru.
     */
    public function dashboard()
    {
        // ─── STATISTIK UMUM ───
        $totalMessages = Message::where('is_spam', false)->count();        // total pesan publik
        $todayMessages = Message::where('is_spam', false)->whereDate('created_at', Carbon::today())->count(); // pesan masuk hari ini
        $totalSenders  = Message::where('is_spam', false)->whereNotNull('sender_name')->distinct('sender_name')->count('sender_name'); // jumlah pengirim yang kasih nama
        $totalKelas    = Message::where('is_spam', false)->distinct('kelas')->count('kelas'); // jumlah kelas unik

        // ─── STATISTIK LAGU ───
        // Pesan dianggap "punya lagu" kalau ada judul lagu ATAU spotify id ATAU youtube id
        $songsCount = Message::where('is_spam', false)->where(function ($q) {
            $q->whereNotNull('song_title')
                ->orWhereNotNull('spotify_track_id')
                ->orWhereNotNull('youtube_video_id');
        })->count();
        $noSongsCount = $totalMessages - $songsCount;
        // Lagu unik (judul + artis) dan artis unik — diproses di PHP biar jalan di MySQL & SQLite
        $uniqueSongs  = Message::where('is_spam', false)->whereNotNull('song_title')
            ->get(['song_title', 'song_artist'])
            ->unique(fn ($m) => strtolower($m->song_title) . '|' . strtolower($m->song_artist))
            ->count();
        $uniqueArtists = Message::where('is_spam', false)->whereNotNull('song_artist')
            ->distinct('song_artist')
            ->count('song_artist');

        // ─── TOP LAGU (5 LAGU PALING SERING DIKIRIM) ───
        $topSongs = Message::where('is_spam', false)->whereNotNull('song_title')
            ->select('song_title', 'song_artist', 'cover_url')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('song_title', 'song_artist', 'cover_url')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // ─── GRAFIK: JUMLAH PESAN 14 HARI TERAKHIR ───
        // Ambil pesan seminggu terakhir, group per tanggal, terus isi kekosongan dengan 0
        $days = collect(range(13, 0))->map(function ($i) {
            return Carbon::today()->subDays($i);
        });

        $countsByDate = Message::where('is_spam', false)->where('created_at', '>=', Carbon::today()->subDays(13))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $chartLabels = $days->map(fn ($d) => $d->format('d M'));
        $chartData   = $days->map(fn ($d) => (int) $countsByDate->get($d->toDateString(), 0));

        // ─── KELAS TERPOPULER (TOP 5) ───
        $topKelas = Message::where('is_spam', false)->select('kelas')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('kelas')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // ─── PESAN TERBARU (5) ───
        $latestMessages = Message::where('is_spam', false)->latest()->limit(5)->get();
        $spamCount = Message::where('is_spam', true)->count();

        // ─── STIKER (kelola lewat dashboard admin) ───
        $stickers = Sticker::latest()->get();

        return view('admin.dashboard', compact(
            'totalMessages',
            'todayMessages',
            'totalSenders',
            'totalKelas',
            'songsCount',
            'noSongsCount',
            'uniqueSongs',
            'uniqueArtists',
            'chartLabels',
            'chartData',
            'topKelas',
            'topSongs',
            'latestMessages',
            'spamCount',
            'stickers'
        ));
    }

    /**
     * ─── TAMBAH STIKER BARU (khusus admin via dashboard) ───
     * Unggah gambar stiker (jpeg/png/webp, maks 2MB) → disimpan di
     * storage/app/public/stickers → tersedia buat picker balasan publik.
     */
    public function storeSticker(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'sticker' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        $path = $request->file('sticker')->store('stickers', 'public');

        $sticker = Sticker::create([
            'name' => isset($validated['name']) && trim($validated['name']) !== ''
                ? trim($validated['name'])
                : null,
            'path' => $path,
            'is_active' => true,
        ]);

        app(AuditService::class)->log('stickers.store', 'sticker', $sticker->id, [
            'name' => $sticker->name,
            'path' => $sticker->path,
        ]);

        return back()->with('success', 'Stiker berhasil ditambahkan — langsung bisa dipakai di balasan.');
    }

    /**
     * ─── HAPUS STIKER (khusus admin via dashboard) ───
     * Hapus file + record stiker. Balasan lama yang memakai stiker ini
     * tetap aman (path disalin ke kolom sticker_path balasan, tidak referensi).
     */
    public function destroySticker(Sticker $sticker)
    {
        app(AuditService::class)->log('stickers.destroy', 'sticker', $sticker->id, [
            'name' => $sticker->name,
            'path' => $sticker->path,
        ]);

        \Illuminate\Support\Facades\Storage::disk('public')->delete($sticker->path);
        $sticker->delete();

        return back()->with('success', 'Stiker berhasil dihapus.');
    }

    /**
     * ─── HALAMAN SEMUA PESAN ───
     * Tabel lengkap semua pesan: siapa yang kirim (sender_name / Anonim),
     * buat siapa, kelas, isi pesan, link lagu, dan waktu kirim.
     * Bisa difilter dengan ?search=... dan ?kelas=...
     */
    public function messages(Request $request)
    {
        $query = Message::query()->where('is_spam', false);

        // Filter pencarian: cocokin sama nama pengirim ATAU nama penerima
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sender_name', 'like', '%' . $search . '%')
                    ->orWhere('recipient_name', 'like', '%' . $search . '%');
            });
        }

        // Filter kelas
        if ($request->filled('kelas')) {
            $query->where('kelas', $request->kelas);
        }

        $messages = $query->pinnedFirst()->paginate(15)->withQueryString()
            ->onEachSide(1);

        // Daftar kelas unik dari database buat dropdown filter
        $kelasList = Message::where('is_spam', false)->distinct('kelas')->orderBy('kelas')->pluck('kelas');

        return view('admin.messages', compact('messages', 'kelasList'));
    }

    /**
     * ─── PIN / LEPAS PIN PESAN ───
     * Toggle is_pinned: pesan ter-pin tampil paling atas di feed publik.
     * pinned_at diisi saat di-pin, dikosongkan saat dilepas.
     */
    public function togglePin(Message $message, AuditService $audit)
    {
        $pinned = ! $message->is_pinned;

        $message->update([
            'is_pinned' => $pinned,
            'pinned_at' => $pinned ? now() : null,
        ]);

        $audit->log($pinned ? 'messages.pin' : 'messages.unpin', 'message', $message->id, [
            'recipient' => $message->recipient_name,
        ]);

        return back()->with('success', $pinned
            ? 'Pesan berhasil di-pin — tampil paling atas di halaman browse.'
            : 'Pin pesan dilepas.');
    }

    /**
     * ─── HALAMAN JEJAK HACKING ───
     * Rekap percobaan mencurigakan dari luar (injeksi, probing file sensitif, brute-force login)
     * yang terekam otomatis oleh middleware TrackHackAttempt. Bisa difilter ?severity=... dan ?search=...
     */
    public function hackTraces(Request $request)
    {
        $query = HackAttempt::query();

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ip_address', 'like', '%' . $search . '%')
                    ->orWhere('path', 'like', '%' . $search . '%')
                    ->orWhere('reason', 'like', '%' . $search . '%')
                    ->orWhere('signature', 'like', '%' . $search . '%');
            });
        }

        $attempts = $query->latest()->paginate(20)->withQueryString()->onEachSide(1);

        $stats = [
            'total' => HackAttempt::count(),
            'today' => HackAttempt::whereDate('created_at', Carbon::today())->count(),
            'critical' => HackAttempt::where('severity', 'critical')->count(),
            'uniqueIps' => HackAttempt::distinct('ip_address')->count('ip_address'),
            'newCount' => HackAttempt::where('is_new', true)->count(),
        ];

        return view('admin.hack', compact('attempts', 'stats'));
    }

    /**
     * Tandai semua jejak hacking sebagai sudah dibaca.
     */
    public function markHackAttemptsRead()
    {
        app(AuditService::class)->log('hack.read-all', 'hack_attempt', null, [
            'marked' => HackAttempt::where('is_new', true)->count(),
        ]);
        HackAttempt::where('is_new', true)->update(['is_new' => false]);

        return back()->with('success', 'Semua jejak hacking ditandai sudah dibaca.');
    }

    /**
     * Hapus satu baris jejak hacking.
     */
    public function destroyHackAttempt(HackAttempt $attempt)
    {
        app(AuditService::class)->log('hack.destroy', 'hack_attempt', $attempt->id, [
            'signature' => $attempt->signature,
            'ip' => $attempt->ip_address,
        ]);
        $attempt->delete();

        return back()->with('success', 'Satu jejak hacking dihapus.');
    }

    /**
     * Kosongkan semua jejak hacking.
     */
    public function clearHackAttempts()
    {
        app(AuditService::class)->log('hack.clear', 'hack_attempt', null, [
            'deleted' => HackAttempt::count(),
        ]);
        HackAttempt::query()->delete();

        return back()->with('success', 'Semua jejak hacking telah dihapus.');
    }

    /**
     * ─── HALAMAN BALASAN (KOMENTAR) ───
     * Semua balasan/komentar di semua pesan, biar admin bisa mengawasi
     * kalau ada komentar nyeleneh. Bisa dicari ?search=... (nama/isi/IP).
     */
    public function replies(Request $request)
    {
        $query = MessageReply::query()->with('message', 'parent');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sender_name', 'like', '%' . $search . '%')
                    ->orWhere('body', 'like', '%' . $search . '%')
                    ->orWhere('ip_address', 'like', '%' . $search . '%');
            });
        }

        $replies = $query->latest()->paginate(20)->withQueryString()->onEachSide(1);

        $stats = [
            'total' => MessageReply::count(),
            'today' => MessageReply::whereDate('created_at', Carbon::today())->count(),
            'uniqueIps' => MessageReply::distinct('ip_address')->count('ip_address'),
        ];

        return view('admin.replies', compact('replies', 'stats'));
    }

    /**
     * ─── HAPUS SATU BALASAN (moderasi komentar nyeleneh) ───
     * Hanya menghapus balasannya; pesan induk tetap aman.
     */
    public function destroyReply(MessageReply $reply)
    {
        app(AuditService::class)->log('replies.destroy', 'reply', $reply->id, [
            'body' => Str::limit($reply->body, 60),
            'message_id' => $reply->message_id,
        ]);
        $reply->delete();

        return back()->with('success', 'Balasan berhasil dihapus.');
    }

    /**
     * ─── HALAMAN SARAN & KRITIK ───
     * Semua masukan pengunjung (saran & kritik) yang dikirim lewat modal
     * setelah mereka berhasil mengirim story. Bisa dicari ?search=...
     */
    public function feedbacks(Request $request)
    {
        $query = Feedback::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('saran', 'like', '%' . $search . '%')
                    ->orWhere('kritik', 'like', '%' . $search . '%')
                    ->orWhere('ip_address', 'like', '%' . $search . '%');
            });
        }

        $feedbacks = $query->latest()->paginate(20)->withQueryString()->onEachSide(1);

        $stats = [
            'total' => Feedback::count(),
            'today' => Feedback::whereDate('created_at', Carbon::today())->count(),
            'withSaran' => Feedback::whereNotNull('saran')->count(),
        ];

        return view('admin.feedbacks', compact('feedbacks', 'stats'));
    }

    /**
     * Hapus satu masukan saran & kritik.
     */
    public function destroyFeedback(Feedback $feedback)
    {
        app(AuditService::class)->log('feedbacks.destroy', 'feedback', $feedback->id, [
            'saran' => Str::limit((string) $feedback->saran, 50),
            'kritik' => Str::limit((string) $feedback->kritik, 50),
        ]);
        $feedback->delete();

        return back()->with('success', 'Masukan berhasil dihapus.');
    }

    /**
     * ─── HALAMAN LAPORAN ───
     * Laporan pengunjung tentang pesan yang dianggap tidak pantas.
     * Admin bisa: tandai selesai, ban IP pengirim pesan, hapus pesannya, atau hapus laporannya.
     * Difilter ?status=open|resolved (default semua).
     */
    public function reports(Request $request)
    {
        $query = MessageReport::query()->with('message');

        if ($request->filled('status')) {
            $query->where('is_resolved', $request->status === 'resolved');
        }

        $reports = $query->latest()->paginate(20)->withQueryString()->onEachSide(1);

        $stats = [
            'total' => MessageReport::count(),
            'open' => MessageReport::where('is_resolved', false)->count(),
        ];

        return view('admin.reports', compact('reports', 'stats'));
    }

    /**
     * Tandai laporan sebagai sudah selesai ditangani.
     */
    public function resolveReport(MessageReport $report)
    {
        $report->update(['is_resolved' => true]);

        app(AuditService::class)->log('reports.resolve', 'report', $report->id, [
            'reason' => $report->reason,
        ]);

        return back()->with('success', 'Laporan ditandai selesai.');
    }

    /**
     * ─── BAN IP PENGIRIM PESAN DARI LAPORAN ───
     * IP pengirim (bukan pelapor) langsung diblokir seumur hidup via tabel spam_bans.
     * Karena nama pengirim tidak diketahui, pakai sender_key '*' (wildcard) biar
     * middleware CheckSpamBan memblokir SEMUA pengiriman dari IP tersebut.
     */
    public function banReportIp(MessageReport $report)
    {
        $ip = $report->message?->ip_address;
        if (! $ip) {
            return back()->with('error', 'IP pengirim pesan tidak ditemukan.');
        }

        SpamBan::updateOrCreate(
            [
                'sender_key' => '*',
                'ip_address' => $ip,
            ],
            [
                'sender_name' => $report->message?->sender_name,
                'spam_count' => Message::where('ip_address', $ip)->where('is_spam', false)->count(),
                'reason' => 'Diblokir manual dari laporan pengunjung.',
                'banned_at' => now(),
                'ban_source' => 'manual',
                'banned_by' => auth()->id(),
            ]
        );

        $report->update(['is_resolved' => true]);

        app(AuditService::class)->log('reports.ban-ip', 'report', $report->id, [
            'ip_address' => $ip,
            'reason' => $report->reason,
        ]);

        return back()->with('success', "IP {$ip} berhasil diblokir — pengiriman dari IP ini sekarang diblokir.");
    }

    /**
     * Hapus pesan yang dilaporkan (semua balasan/reaksi ikut terhapus via cascade).
     */
    public function destroyReportedMessage(MessageReport $report)
    {
        $message = $report->message;

        app(AuditService::class)->log('reports.delete-message', 'report', $report->id, [
            'message_id' => $message?->id,
            'recipient' => $message?->recipient_name,
        ]);
        $message?->delete();

        return back()->with('success', $message
            ? 'Pesan yang dilaporkan berhasil dihapus.'
            : 'Pesan sudah tidak ada lagi.');
    }

    /**
     * Hapus satu baris laporan (tanpa menghapus pesannya).
     */
    public function destroyReport(MessageReport $report)
    {
        app(AuditService::class)->log('reports.destroy', 'report', $report->id, [
            'reason' => $report->reason,
        ]);
        $report->delete();

        return back()->with('success', 'Laporan dihapus.');
    }

    /**
     * ─── HALAMAN SPAM ───
     * Menampilkan pengirim spam yang dikelompokkan berdasarkan nama + IP.
     */
    public function spam(SpamDetectionService $spam)
    {
        $offenders = $spam->topOffenders()->paginate(20)->withQueryString();
        $bans = SpamBan::query()
            ->orderByDesc('banned_at')
            ->paginate(20, ['*'], 'bans_page');

        return view('admin.spam', compact('offenders', 'bans'));
    }

    /**
     * Hapus semua pesan milik kombinasi nama + IP setelah konfirmasi admin.
     */
    public function destroySpamGroup(Request $request)
    {
        $validated = $request->validate([
            'sender_key' => 'required|string|max:255',
            'ip_address' => 'required|ip',
        ]);

        $deleted = DB::transaction(fn () => Message::query()
            ->where('sender_key', $validated['sender_key'])
            ->where('ip_address', $validated['ip_address'])
            ->delete());

        app(AuditService::class)->log('spam.destroy-group', 'spam', null, [
            'sender_key' => $validated['sender_key'],
            'ip_address' => $validated['ip_address'],
            'deleted' => $deleted,
        ]);

        return redirect()->route('admin.spam')
            ->with('success', "{$deleted} pesan dari identitas tersebut berhasil dihapus.");
    }

    /**
     * ─── HAPUS PESAN ───
     * Delete message dari database (buat moderasi konten).
     */
    public function destroy(Message $message)
    {
        $audit = app(AuditService::class);
        $audit->log('messages.destroy', 'message', $message->id, [
            'recipient' => $message->recipient_name,
            'kelas' => $message->kelas,
        ]);
        $message->delete();

        return redirect()->route('admin.messages')
            ->with('success', 'Pesan berhasil dihapus.');
    }

    /**
     * ─── RESOLVE ULANG LAGU KE YOUTUBE ───
     * Ngelengkapi pesan yang punya judul/artis lagu tapi belum punya youtube_video_id
     * (misalnya karena API key YouTube dulu invalid / resolve gagal pas ngirim).
     * Dipanggil dari tombol "resolve" di kolom Lagu halaman admin.
     */
    public function resolveSong(Message $message, YouTubeService $youtube)
    {
        if (! $message->song_title) {
            return back()->with('error', 'Pesan ini tidak punya judul lagu — tidak bisa di-resolve.');
        }

        $match = $youtube->searchAudio($message->song_title, (string) $message->song_artist);

        if (! $match || empty($match['youtube_id'])) {
            return back()->with('error', "Resolve gagal untuk \"{$message->song_title}\" — audio tidak ditemukan.");
        }

        $message->update(['youtube_video_id' => $match['youtube_id']]);

        app(AuditService::class)->log('messages.resolve-song', 'message', $message->id, [
            'song' => $message->song_title,
        ]);

        return back()->with('success', "Lagu \"{$message->song_title}\" berhasil di-resolve ke YouTube.");
    }

    /**
     * ─── PERPUSTAKAAN LAGU ───
     * Daftar lagu unik yang pernah dipakai di semua pesan: cover, judul, artis,
     * berapa kali dikirim, lagu terakhir dipakai, plus tombol putar (YouTube/Spotify).
     * Bisa dicari via ?search=... dan difilter ?kelas=...
     */
    public function songs(Request $request)
    {
        $query = Message::query()
            ->where('is_spam', false)
            ->whereNotNull('song_title')
            ->select('song_title', 'song_artist', 'cover_url')
            ->selectRaw('COUNT(*) as total, MIN(id) as sample_id, MAX(created_at) as last_used_at')
            ->groupBy('song_title', 'song_artist', 'cover_url');

        // Filter pencarian judul/artis
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('song_title', 'like', '%' . $search . '%')
                    ->orWhere('song_artist', 'like', '%' . $search . '%');
            });
        }

        // Filter kelas (pesan asal lagu itu dikirim)
        if ($request->filled('kelas')) {
            $query->where('kelas', $request->kelas);
        }

        $songs = $query->orderByDesc('total')->paginate(15)->withQueryString()->onEachSide(1);

        // Ambil 1 contoh youtube/spotify id per lagu (biar tombol putar jalan tanpa nambah query N+1)
        $sampleIds = $songs->pluck('sample_id')->filter()->all();
        $links = collect();
        Message::whereIn('id', $sampleIds)
            ->get(['song_title', 'song_artist', 'youtube_video_id', 'spotify_track_id'])
            ->each(function ($m) use ($links) {
                $key = strtolower($m->song_title) . '|' . strtolower($m->song_artist);
                $cur = $links->get($key, ['youtube' => null, 'spotify' => null]);
                if (empty($cur['youtube']) && $m->youtube_video_id) {
                    $cur['youtube'] = $m->youtube_video_id;
                }
                if (empty($cur['spotify']) && $m->spotify_track_id) {
                    $cur['spotify'] = $m->spotify_track_id;
                }
                $links->put($key, $cur);
            });

        $kelasList = Message::where('is_spam', false)->distinct('kelas')->orderBy('kelas')->pluck('kelas');

        return view('admin.songs', compact('songs', 'links', 'kelasList'));
    }

    /**
     * ─── REKAP PER KELAS ───
     * Statistik tiap kelas: jumlah pesan, pesan yang pake lagu, lagu unik,
     * pesan terakhir, dan link ke daftar pesan kelas itu.
     */
    public function kelas()
    {
        $kelas = Message::where('is_spam', false)->select('kelas')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN song_title IS NOT NULL OR spotify_track_id IS NOT NULL OR youtube_video_id IS NOT NULL THEN 1 ELSE 0 END) as with_song')
            ->selectRaw('COUNT(DISTINCT song_title) as unique_songs')
            ->selectRaw('MAX(created_at) as last_at')
            ->selectRaw('MIN(created_at) as first_at')
            ->groupBy('kelas')
            ->orderByDesc('total')
            ->get();

        return view('admin.kelas', compact('kelas'));
    }

    /**
     * ─── HALAMAN EXPORT DATA ───
     * Tempat download data dalam format CSV (pesan / lagu), opsi filter rentang tanggal.
     */
    public function export(Request $request)
    {
        return view('admin.export');
    }

    /**
     * ─── EXPORT PESAN KE CSV ───
     * Stream langsung sebagai file CSV. Bisa difilter ?from=YYYY-MM-DD&to=YYYY-MM-DD.
     */
    public function exportMessagesCsv(Request $request)
    {
        $query = Message::query()->where('is_spam', false);

        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('created_at', [$request->from . ' 00:00:00', $request->to . ' 23:59:59']);
        }

        $messages = $query->orderByDesc('created_at')->get();

        $rows = [['ID', 'Dari', 'IP', 'Untuk', 'Kelas', 'Pesan', 'Judul Lagu', 'Artis', 'Spotify ID', 'YouTube ID', 'Waktu Kirim']];
        foreach ($messages as $m) {
            $rows[] = [
                $m->id,
                $m->sender_name ?: 'Anonim',
                $m->ip_address ?: '',
                $m->recipient_name,
                $m->kelas,
                $m->message,
                $m->song_title ?: '',
                $m->song_artist ?: '',
                $m->spotify_track_id ?: '',
                $m->youtube_video_id ?: '',
                $m->created_at?->format('Y-m-d H:i:s'),
            ];
        }

        return $this->csvResponse("pesan-{$this->exportDateRange($request)}.csv", $rows);
    }

    /**
     * ─── EXPORT LAGU KE CSV ───
     * Lagu unik + berapa kali dikirim + kapan terakhir dipakai.
     */
    public function exportSongsCsv(Request $request)
    {
        $songs = Message::where('is_spam', false)->whereNotNull('song_title')
            ->select('song_title', 'song_artist', 'cover_url')
            ->selectRaw('COUNT(*) as total, MAX(created_at) as last_used_at')
            ->groupBy('song_title', 'song_artist', 'cover_url')
            ->orderByDesc('total')
            ->get();

        $rows = [['Judul Lagu', 'Artis', 'Berapa Kali Dikirim', 'Terakhir Dikirim']];
        foreach ($songs as $s) {
            $rows[] = [
                $s->song_title,
                $s->song_artist ?: '',
                $s->total,
                $s->last_used_at ? \Illuminate\Support\Carbon::parse($s->last_used_at)->format('Y-m-d H:i:s') : '',
            ];
        }

        return $this->csvResponse("lagu-{$this->exportDateRange($request)}.csv", $rows);
    }

    /**
     * ─── EXPORT LOG AUDIT KE CSV ───
     * Semua aktivitas admin: waktu, admin, aksi, target, detail, IP.
     */
    public function exportAuditCsv(Request $request)
    {
        app(AuditService::class)->log('export.audit', null, null, [
            'rows' => AuditLog::count(),
        ]);

        $query = AuditLog::query();

        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('created_at', [$request->from . ' 00:00:00', $request->to . ' 23:59:59']);
        }

        $logs = $query->orderByDesc('created_at')->get();

        $rows = [['Waktu', 'Admin', 'Aksi', 'Target', 'Detail', 'IP Address']];
        foreach ($logs as $log) {
            $rows[] = [
                $log->created_at?->format('Y-m-d H:i:s'),
                $log->user_name ?: 'Sistem',
                $log->action,
                $log->target_type ? $log->target_type . '#' . $log->target_id : '',
                $log->details ? json_encode($log->details, JSON_UNESCAPED_UNICODE) : '',
                $log->ip_address ?: '',
            ];
        }

        return $this->csvResponse("audit-{$this->exportDateRange($request)}.csv", $rows);
    }

    /**
     * ─── EXPORT RIWAYAT LOGIN KE CSV ───
     * Semua percobaan login admin: waktu, email, status, mencurigakan?, IP.
     */
    public function exportLoginsCsv(Request $request)
    {
        app(AuditService::class)->log('export.logins', null, null, [
            'rows' => LoginLog::count(),
        ]);

        $query = LoginLog::query();

        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('created_at', [$request->from . ' 00:00:00', $request->to . ' 23:59:59']);
        }

        $logs = $query->orderByDesc('created_at')->get();

        $rows = [['Waktu', 'Email', 'Status', 'Mencurigakan', 'IP Address']];
        foreach ($logs as $log) {
            $rows[] = [
                $log->created_at?->format('Y-m-d H:i:s'),
                $log->email,
                $log->status,
                $log->is_suspicious ? 'Ya' : 'Tidak',
                $log->ip_address ?: '',
            ];
        }

        return $this->csvResponse("login-{$this->exportDateRange($request)}.csv", $rows);
    }

    /**
     * ─── EXPORT JEJAK HACKING KE CSV ───
     * Semua percobaan serangan yang terdeteksi: waktu, severitas, IP, target, alasan.
     */
    public function exportHackCsv(Request $request)
    {
        app(AuditService::class)->log('export.hack', null, null, [
            'rows' => HackAttempt::count(),
        ]);

        $query = HackAttempt::query();

        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('created_at', [$request->from . ' 00:00:00', $request->to . ' 23:59:59']);
        }

        $attempts = $query->orderByDesc('created_at')->get();

        $rows = [['Waktu', 'Severitas', 'Jenis', 'IP Address', 'Method', 'Target', 'Alasan']];
        foreach ($attempts as $a) {
            $rows[] = [
                $a->created_at?->format('Y-m-d H:i:s'),
                $a->severity,
                $a->signature,
                $a->ip_address,
                $a->method,
                $a->path,
                $a->reason,
            ];
        }

        return $this->csvResponse("hack-{$this->exportDateRange($request)}.csv", $rows);
    }

    /**
     * ─── BANTUAN: BIKIN RESPONSE CSV ───
     * String CSV dipecah per kolom dengan escaping, terus dikirim dengan header
     * Content-Type text/csv. BOM UTF-8 ditambah biar Excel nampilin huruf dengan bener.
     */
    private function csvResponse(string $filename, array $rows): \Illuminate\Http\Response
    {
        $csv = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($csv, $row);
        }
        rewind($csv);
        $body = stream_get_contents($csv);
        fclose($csv);

        $response = response($body, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);

        return $response;
    }

    /**
     * ─── BANTUAN: SUFFIX RENTANG TANGGAL BUAT NAMA FILE ───
     */
    private function exportDateRange(Request $request): string
    {
        if ($request->filled('from') && $request->filled('to')) {
            return $request->from . '_sd_' . $request->to;
        }

        return 'semua';
    }
}
