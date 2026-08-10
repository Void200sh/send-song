<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Services\SpamDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    /**
     * ─── METHOD INDEX — TAMPILIN HALAMAN BROWSE ───
     * Dipanggil pas user buka GET /messages
     * Bisa nerima query parameter: ?search=... &kelas=...
     */
    public function index(Request $request)
    {
        // Pesan yang ditandai spam tidak pernah masuk feed publik.
        $query = Message::query()->where('is_spam', false);

        // ─── DAFTAR KELAS ───
        // Hardcode 33 kelas (X, XI, XII × (PPLG/AKL/MPLB 3 rombel, PM 2 rombel))
        // Dipake buat dropdown filter di halaman browse
        $kelasList = [
            'X PPLG 1', 'X PPLG 2', 'X PPLG 3',
            'X PM 1', 'X PM 2',
            'X AKL 1', 'X AKL 2', 'X AKL 3',
            'X MPLB 1', 'X MPLB 2', 'X MPLB 3', 
            'XI PPLG 1', 'XI PPLG 2', 'XI PPLG 3',
            'XI PM 1', 'XI PM 2',
            'XI AKL 1', 'XI AKL 2', 'XI AKL 3',
            'XI MPLB 1', 'XI MPLB 2', 'XI MPLB 3',
            'XII PPLG 1', 'XII PPLG 2', 'XII PPLG 3',
            'XII PM 1', 'XII PM 2',
            'XII AKL 1', 'XII AKL 2', 'XII AKL 3',
            'XII MPLB 1', 'XII MPLB 2', 'XII MPLB 3',
        ];

        // ─── FILTER PENCARIAN BERDASARKAN NAMA ───
        // Kalo ada parameter ?search=xxx di URL, filter pesan yang recipient_name-nya mengandung kata xxx
        // Pake LIKE %...% biar partial match (misal "Rina" ketemu "Rinaldi", "Sarina", dll)
        if ($request->filled('search')) {
            $query->where('recipient_name', 'like', '%' . $request->search . '%');
        }

        // ─── FILTER BERDASARKAN KELAS ───
        // Kalo ada parameter ?kelas=xxx di URL, filter pesan yang kelas-nya sama persis
        if ($request->filled('kelas')) {
            $query->where('kelas', $request->kelas);
        }

        // ─── EKSEKUSI QUERY ───
        // latest() = urutkan dari yang terbaru (created_at DESC)
        // paginate(12) = ambil 12 pesan per halaman, sisanya dipaginasi
        $messages = $query->latest()->paginate(12);

        // Simpan nilai filter buat dikirim ke view (biar inputnya gak hilang pas dirender)
        $selectedKelas = $request->kelas;
        $search = $request->search;

        // Render view messages/index.blade.php dengan 4 data
        return view('messages.index', compact('messages', 'kelasList', 'selectedKelas', 'search'));
    }

    /**
     * ─── METHOD SHOW — TAMPILIN HALAMAN DETAIL SATU PESAN ───
     * Dipanggil pas user buka GET /messages/{id}
     * Route model binding otomatis nyari Message sesuai id, 404 kalo gak ada
     */
    public function show(Message $message)
    {
        abort_if($message->is_spam, 404);

        return view('messages.show', compact('message'));
    }

    /**
     * ─── METHOD STORE — SIMPAN PESAN BARU ───
     * Dipanggil pas user submit form (POST /messages)
     */
    public function store(Request $request, SpamDetectionService $spam)
    {
        $identity = $spam->identity($request);
        $spamAssessment = $spam->assess($identity, (string) $request->input('message'));

        // ─── VALIDASI ───
        // Pastikan data yang dikirim sesuai aturan:
        // - sender_name: opsional (nullable), harus string, maksimal 255 karakter
        // - recipient_name: wajib, harus string, maksimal 255 karakter
        // - kelas: wajib, harus string, maksimal 50 karakter
        // - message: wajib, harus string
        // - spotify_track_id / song_title / song_artist / cover_url / youtube_video_id: opsional (nullable)
        // Kalo validasi gagal, Laravel otomatis balikin user ke halaman sebelumnya + error messages
        $validated = $request->validate([
            'sender_name' => 'nullable|string|max:255',
            'recipient_name' => 'required|string|max:255',
            'kelas' => 'required|string|max:50',
            'message' => 'required|string',
            'spotify_track_id' => 'nullable|string|max:50',
            'song_title' => 'nullable|string|max:255',
            'song_artist' => 'nullable|string|max:255',
            'cover_url' => 'nullable|string|max:1000',
            'youtube_video_id' => 'nullable|string|max:50',
            'spotify_url' => 'nullable|string|max:500',
            'clip_start_seconds' => 'nullable|integer|min:0|max:600',
            'clip_end_seconds' => 'nullable|integer|min:1|max:600',
            'duration_seconds' => 'nullable|integer|min:0|max:3600',
        ]);

        // ─── NORMALISASI NAMA PENGIRIM ───
        // Kalo kolom sender_name diisi cuma spasi/whitespace, anggap aja anonim (NULL)
        // Biar di admin gak nampil "   " yang jelek
        if (isset($validated['sender_name']) && trim($validated['sender_name']) === '') {
            $validated['sender_name'] = null;
        }

        // ─── NORMALISASI KLIP LAGU ───
        // Klip valid = mulai & selesai terisi, dan selesai > mulai.
        // Kalo gak lengkap / gak valid, treat sebagai full lagu (keduanya NULL).
        $start = $request->filled('clip_start_seconds')
            ? (int) $validated['clip_start_seconds']
            : null;
        $end = $request->filled('clip_end_seconds')
            ? (int) $validated['clip_end_seconds']
            : null;

        $validated['clip_start_seconds'] = null;
        $validated['clip_end_seconds'] = null;

        if ($start !== null && $end !== null && $end > $start) {
            $validated['clip_start_seconds'] = $start;
            $validated['clip_end_seconds'] = $end;
        }

        // ─── EKSTRAK SPOTIFY TRACK ID (fallback link lama) ───
        // Kalo user paste link spotify_url langsung (tanpa pakai pencarian lagu),
        // ambil track ID-nya pake regex sebagai fallback
        if ($request->filled('spotify_url') && ! $request->filled('spotify_track_id')) {
            $trackId = $this->extractSpotifyTrackId($request->spotify_url);
            // Simpan ID hasil ekstraksi ke array validated (pake nama kolom DB: spotify_track_id)
            $validated['spotify_track_id'] = $trackId;
        }

        // Hapus spotify_url dari array validated — soalnya kolom di DB namanya spotify_track_id
        // Kalo gak dihapus, Laravel bakal nyoba nyimpen kolom spotify_url yang gak ada di tabel → error
        unset($validated['spotify_url']);

        // ─── ANTI-SPAM ───
        // IP dan identitas ter-normalisasi disimpan hanya untuk pesan baru.
        $validated = array_merge($validated, $identity, [
            'is_spam' => $spamAssessment['is_spam'],
            'spam_reason' => $spamAssessment['spam_reason'],
            'spam_fingerprint' => $spamAssessment['spam_fingerprint'],
        ]);

        // Pesan spam tetap disimpan untuk audit admin, tetapi disembunyikan dari publik.
        $message = DB::transaction(fn () => Message::create($validated));
        $spam->recordAndMaybeBan($message);

        return redirect()->route('messages.index')
            ->with('success', $message->is_spam
                ? 'Pesan diterima dan sedang diperiksa.'
                : 'Pesan berhasil dikirim!');
    }

    /**
     * ─── METHOD EKSTRAK SPOTIFY TRACK ID ───
     * Ngambil ID unik track Spotify dari URL yang dikasih user
     * Contoh: "https://open.spotify.com/track/4PTG3Z6ehG3jhSMvqY4QFY" → "4PTG3Z6ehG3jhSMvqY4QFY"
     */
    private function extractSpotifyTrackId(string $url): ?string
    {
        // Regex: nyari pattern "spotify.com/track/" terus diikuti karakter alfanumerik
        // ([a-zA-Z0-9]+) = capture group — nangkep ID track-nya
        preg_match('/spotify\.com\/track\/([a-zA-Z0-9]+)/', $url, $matches);
        // Kalo cocok, return $matches[1] (ID). Kalo gak cocok, return null
        return $matches[1] ?? null;
    }
}
