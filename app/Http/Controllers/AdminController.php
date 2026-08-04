<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
        $totalMessages = Message::count();                                  // total semua pesan
        $todayMessages = Message::whereDate('created_at', Carbon::today())->count(); // pesan masuk hari ini
        $totalSenders  = Message::whereNotNull('sender_name')->distinct('sender_name')->count('sender_name'); // jumlah pengirim yang kasih nama
        $totalKelas    = Message::distinct('kelas')->count('kelas');        // jumlah kelas unik

        // ─── GRAFIK: JUMLAH PESAN 14 HARI TERAKHIR ───
        // Ambil pesan seminggu terakhir, group per tanggal, terus isi kekosongan dengan 0
        $days = collect(range(13, 0))->map(function ($i) {
            return Carbon::today()->subDays($i);
        });

        $countsByDate = Message::where('created_at', '>=', Carbon::today()->subDays(13))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $chartLabels = $days->map(fn ($d) => $d->format('d M'));
        $chartData   = $days->map(fn ($d) => (int) $countsByDate->get($d->toDateString(), 0));

        // ─── KELAS TERPOPULER (TOP 5) ───
        $topKelas = Message::select('kelas')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('kelas')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // ─── PESAN TERBARU (5) ───
        $latestMessages = Message::latest()->limit(5)->get();

        return view('admin.dashboard', compact(
            'totalMessages',
            'todayMessages',
            'totalSenders',
            'totalKelas',
            'chartLabels',
            'chartData',
            'topKelas',
            'latestMessages'
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
        $query = Message::query();

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
        $kelasList = Message::distinct('kelas')->orderBy('kelas')->pluck('kelas');

        return view('admin.messages', compact('messages', 'kelasList'));
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
}
