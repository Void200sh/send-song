<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\MessageReply;
use App\Models\MessageReport;
use App\Models\MessageView;
use App\Models\ReplyReaction;
use App\Services\SpamDetectionService;
use App\Services\YouTubeService;
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

        $messages = $query->pinnedFirst()->paginate(12);

        // Kartu yang tampil di halaman ini langsung dihitung sebagai views
        // (total +1 per pesan, unik per IP lewat tabel message_views).
        $this->recordViews($messages, $request);

        // Load reaksi + jumlah balasan + emoji yang sudah direaksikan pengunjung ini (biar gak N+1)
        $messages->load('reactions');
        $messages->loadCount('replies');
        $myReactions = MessageReaction::where('ip_address', SpamDetectionService::clientIp($request))
            ->whereIn('message_id', $messages->pluck('id'))
            ->get()
            ->groupBy('message_id')
            ->map->pluck('emoji');

        $selectedKelas = $request->kelas;
        $search = $request->search;

        return view('messages.index', compact('messages', 'kelasList', 'selectedKelas', 'search', 'myReactions'));
    }

    /**
     * ─── METHOD SHOW — TAMPILIN HALAMAN DETAIL SATU PESAN ───
     */
    public function show(Message $message)
    {
        abort_if($message->is_spam, 404);

        // Membuka halaman detail juga dihitung sebagai views
        $this->recordViews([$message], request());

        // Reaksi + balasan (beserta reaksi tiap balasan): load sekalian biar gak N+1.
        $message->load('reactions', 'replies.reactions');
        $myReactions = $message->reactions
            ->where('ip_address', SpamDetectionService::clientIp(request()))
            ->pluck('emoji')
            ->all();

        // Emoji yang sudah direaksikan pengunjung ini per balasan (untuk state aktif)
        $myReplyReactions = $message->replies->flatMap(function ($reply) {
            return [$reply->id => $reply->reactions
                ->where('ip_address', SpamDetectionService::clientIp(request()))
                ->pluck('emoji')
                ->all()];
        })->all();

        return view('messages.show', compact('message', 'myReactions', 'myReplyReactions'));
    }

    /**
     * ─── METHOD REPLY — KIRIM BALASAN KE PESAN (thread mini) ───
     * Pengunjung bisa membalas pesan tanpa perlu login. Nama opsional,
     * IP dicatat untuk keperluan moderasi. Redirect balik ke halaman detail.
     */
    public function reply(Request $request, Message $message)
    {
        abort_if($message->is_spam, 404);

        $validated = $request->validate([
            'sender_name' => 'nullable|string|max:255',
            'body' => 'required|string|max:1000',
        ]);

        if (isset($validated['sender_name']) && trim($validated['sender_name']) === '') {
            $validated['sender_name'] = null;
        }

        MessageReply::create([
            'message_id' => $message->id,
            'sender_name' => $validated['sender_name'] ?? null,
            'body' => trim($validated['body']),
            'ip_address' => SpamDetectionService::clientIp($request),
        ]);

        return back()->with('reply_success', 'Balasan terkirim!');
    }

    /**
     * ─── METHOD REACT REPLY — TAMBAH/BATAL REAKSI EMOJI DI BALASAN (toggle) ───
     * Sama persis seperti react() untuk pesan, tapi untuk balasan (komentar).
     * Satu pengunjung maksimal satu reaksi per emoji per balasan (dilacak via IP).
     */
    public function reactReply(Request $request, MessageReply $reply)
    {
        abort_if($reply->message->is_spam, 404);

        $validated = $request->validate([
            'emoji' => ['required', 'string', Rule::in(Message::REACTION_EMOJIS)],
        ]);

        $emoji = $validated['emoji'];
        $ip = SpamDetectionService::clientIp($request);

        $existing = ReplyReaction::where('reply_id', $reply->id)
            ->where('emoji', $emoji)
            ->where('ip_address', $ip)
            ->first();

        DB::transaction(function () use ($existing, $reply, $emoji, $ip) {
            if ($existing) {
                $existing->delete();
            } else {
                ReplyReaction::create([
                    'reply_id' => $reply->id,
                    'emoji' => $emoji,
                    'ip_address' => $ip,
                ]);
            }
        });

        // Hitung ulang jumlah reaksi per emoji buat balasan ini
        $counts = ReplyReaction::where('reply_id', $reply->id)
            ->select('emoji')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('emoji')
            ->pluck('total', 'emoji');

        return response()->json([
            'counts' => $counts,
            'active' => $existing ? null : $emoji,
        ]);
    }

    /**
     * ─── METHOD REPORT — LAPORKAN PESAN (konten tidak pantas) ───
     * Pengunjung menekan tombol lapor → tersimpan di tabel message_reports.
     * Admin melihatnya di halaman admin Laporan dan bisa ban IP / hapus pesan.
     * Dibuat idempoten per (pesan, IP): satu pengunjung hanya 1 laporan per pesan.
     */
    public function report(Request $request, Message $message)
    {
        abort_if($message->is_spam, 404);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $ip = SpamDetectionService::clientIp($request);

        MessageReport::updateOrCreate(
            [
                'message_id' => $message->id,
                'ip_address' => $ip,
            ],
            [
                'reason' => $validated['reason'] ?? null,
                'is_resolved' => false,
            ]
        );

        return response()->json(['ok' => true]);
    }

    /**
     * ─── METHOD REACT — TAMBAH/BATAL REAKSI EMOJI (toggle, ala WhatsApp) ───
     * Pengunjung boleh kasih maksimal satu reaksi per emoji per pesan
     * (dilacak via IP). Klik emoji yang sama lagi = batal. Balas JSON biar
     * frontend bisa update jumlah reaksi tanpa reload halaman.
     */
    public function react(Request $request, Message $message)
    {
        abort_if($message->is_spam, 404);

        $validated = $request->validate([
            'emoji' => ['required', 'string', Rule::in(Message::REACTION_EMOJIS)],
        ]);

        $emoji = $validated['emoji'];
        $ip = SpamDetectionService::clientIp($request);

        $existing = MessageReaction::where('message_id', $message->id)
            ->where('emoji', $emoji)
            ->where('ip_address', $ip)
            ->first();

        DB::transaction(function () use ($existing, $message, $emoji, $ip) {
            if ($existing) {
                $existing->delete();
            } else {
                MessageReaction::create([
                    'message_id' => $message->id,
                    'emoji' => $emoji,
                    'ip_address' => $ip,
                ]);
            }
        });

        // Hitung ulang jumlah reaksi per emoji buat pesan ini
        $counts = MessageReaction::where('message_id', $message->id)
            ->select('emoji')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('emoji')
            ->pluck('total', 'emoji');

        return response()->json([
            'counts' => $counts,
            // Emoji yang sekarang aktif untuk pengunjung ini (null kalau semua batal)
            'active' => $existing ? null : $emoji,
        ]);
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

        // ─── JARING PENGAMAN RESOLVE YOUTUBE ───
        // Tombol submit aktif begitu lagu dipilih, jadi user bisa kirim SEBELUM resolve
        // client selesai → youtube_video_id kosong → lagu gak muncul di feed.
        // Coba resolve di server biar lagunya tetap tampil. Kalau gagal, admin bisa
        // resolve manual lewat tombol "resolve" di halaman admin.
        // Skip kalau pesan spam: gak tampil di feed mana pun, jadi percuma buang kuota API.
        if (($validated['song_title'] ?? null)
            && empty($validated['youtube_video_id'])
            && ! $spamAssessment['is_spam']) {
            try {
                $match = app(YouTubeService::class)->searchAudio(
                    $validated['song_title'],
                    (string) ($validated['song_artist'] ?? '')
                );
                if (! empty($match['youtube_id'])) {
                    $validated['youtube_video_id'] = $match['youtube_id'];
                }
            } catch (\Throwable) {
                // resolve gagal — biarkan null (admin bisa resolve manual)
            }
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

    /**
     * ─── CATAT VIEWS ───
     * Total views (+1 per pesan yang tampil) selalu bertambah. Views unik per
     * IP dijamin lewat tabel message_views — IP yang sama cuma dihitung sekali.
     * Nilai di memori ikut disinkronkan biar halaman yang sama langsung
     * menampilkan angka terbaru tanpa refresh model.
     */
    private function recordViews(iterable $messages, Request $request): void
    {
        $ip = SpamDetectionService::clientIp($request);

        foreach ($messages as $message) {
            $message->increment('views');
            $message->views += 1;

            $created = MessageView::firstOrCreate([
                'message_id' => $message->id,
                'ip_address' => $ip,
            ])->wasRecentlyCreated;

            if ($created) {
                $message->increment('unique_views');
                $message->unique_views += 1;
            }
        }
    }

    private function extractSpotifyTrackId(string $url): ?string
    {
        preg_match('/spotify\.com\/track\/([a-zA-Z0-9]+)/', $url, $matches);

        return $matches[1] ?? null;
    }
}
