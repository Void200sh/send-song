<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\SpamBan;
use App\Services\SpamDetectionService;
use App\Services\YouTubeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
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
            'spamCount'
        ));
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

        $messages = $query->latest()->paginate(15)->withQueryString()
            ->onEachSide(1);

        // Daftar kelas unik dari database buat dropdown filter
        $kelasList = Message::where('is_spam', false)->distinct('kelas')->orderBy('kelas')->pluck('kelas');

        return view('admin.messages', compact('messages', 'kelasList'));
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

        return redirect()->route('admin.spam')
            ->with('success', "{$deleted} pesan dari identitas tersebut berhasil dihapus.");
    }

    /**
     * ─── HAPUS PESAN ───
     * Delete message dari database (buat moderasi konten).
     */
    public function destroy(Message $message)
    {
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
