<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Services\SpamDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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

        if ($request->filled('search')) {
            $query->where('recipient_name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('kelas')) {
            $query->where('kelas', $request->kelas);
        }

        $messages = $query->latest()->paginate(12);
        $selectedKelas = $request->kelas;
        $search = $request->search;

        return view('messages.index', compact('messages', 'kelasList', 'selectedKelas', 'search'));
    }

    /**
     * ─── METHOD SHOW — TAMPILIN HALAMAN DETAIL SATU PESAN ───
     */
    public function show(Message $message)
    {
        abort_if($message->is_spam, 404);

        return view('messages.show', compact('message'));
    }

    /**
     * ─── METHOD STORE — SIMPAN PESAN BARU ───
     */
    public function store(Request $request, SpamDetectionService $spam)
    {
        $identity = $spam->identity($request);
        $spamAssessment = $spam->assess($identity, (string) $request->input('message'));

        $validated = $request->validate([
            'sender_name' => 'nullable|string|max:255',
            'recipient_name' => 'required|string|max:255',
            'kelas' => 'required|string|max:50',
            'message' => 'required|string',
            'theme' => ['nullable', 'string', Rule::in(Message::THEMES)],
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

        if (isset($validated['sender_name']) && trim($validated['sender_name']) === '') {
            $validated['sender_name'] = null;
        }

        // Tema classic disimpan sebagai NULL agar konsisten dengan pesan lama.
        if (($validated['theme'] ?? null) === 'classic') {
            $validated['theme'] = null;
        }

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

        if ($request->filled('spotify_url') && ! $request->filled('spotify_track_id')) {
            $validated['spotify_track_id'] = $this->extractSpotifyTrackId($request->spotify_url);
        }

        unset($validated['spotify_url']);

        // ─── RESOLUSI MERGE: pakai versi remote (anti-spam + identity) ───
        // IP pengirim ditangkap otomatis oleh $spam->identity($request) → kolom ip_address.

        // Cegah submit ganda, tetapi jangan membuang request berulang yang memang sedang ditandai spam.
        $duplicate = Message::where('recipient_name', $validated['recipient_name'])
            ->where('kelas', $validated['kelas'])
            ->where('message', $validated['message'])
            ->where('sender_name', $validated['sender_name'] ?? null)
            ->where('created_at', '>=', now()->subMinutes(2))
            ->exists();

        if ($duplicate && ! $spamAssessment['is_spam']) {
            return back()
                ->withErrors(['message' => 'Pesan dengan isi yang sama sudah terkirim. Silakan coba lagi beberapa menit kemudian.'])
                ->withInput();
        }

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

    private function extractSpotifyTrackId(string $url): ?string
    {
        preg_match('/spotify\.com\/track\/([a-zA-Z0-9]+)/', $url, $matches);

        return $matches[1] ?? null;
    }
}
