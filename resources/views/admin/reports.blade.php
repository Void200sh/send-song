{{-- ─── HALAMAN LAPORAN PESAN ─── --}}
{{-- Laporan pengunjung tentang pesan yang dianggap tidak pantas. Admin bisa tandai selesai, ban IP, hapus pesan. --}}
@extends('admin.layouts.app')

@section('title', 'Laporan Pesan')

@section('content')
    <section class="page">
        <div class="page__header">
            <div class="page__headline">
                <h1 class="page__title font-reenie">Laporan Pesan 🚩</h1>
                <p class="page__description">
                    Pesan yang dilaporkan pengunjung karena dianggap tidak pantas. Tindak lanjuti: ban IP pengirim, hapus pesan, atau tandai selesai.
                </p>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert--success">
                <div class="alert__title">Berhasil</div>
                <div class="alert__description">{{ session('success') }}</div>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert--danger">
                <div class="alert__title">Gagal</div>
                <div class="alert__description">{{ session('error') }}</div>
            </div>
        @endif

        {{-- ─── ROW STATISTIK ─── --}}
        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 sm:col-span-6 xl:col-span-4">
                <div class="card card--stat">
                    <div class="card__body">
                        <div class="stat">
                            <p class="stat__label text-eyebrow">Total Laporan</p>
                            <p class="stat__value font-reenie">{{ number_format($stats['total']) }}</p>
                            <p class="stat__meta text-muted-foreground text-sm">laporan masuk</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-span-12 sm:col-span-6 xl:col-span-4">
                <div class="card card--stat">
                    <div class="card__body">
                        <div class="stat">
                            <p class="stat__label text-eyebrow">Perlu Ditindak</p>
                            <p class="stat__value font-reenie" style="color:{{ $stats['open'] > 0 ? '#dc2626' : 'inherit' }}">{{ number_format($stats['open']) }}</p>
                            <p class="stat__meta text-muted-foreground text-sm">belum ditindaklanjuti</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-span-12 sm:col-span-6 xl:col-span-4">
                <div class="card card--stat">
                    <div class="card__body">
                        <div class="stat">
                            <p class="stat__label text-eyebrow">Selesai</p>
                            <p class="stat__value font-reenie">{{ number_format($stats['total'] - $stats['open']) }}</p>
                            <p class="stat__meta text-muted-foreground text-sm">sudah ditangani</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── FILTER STATUS ─── --}}
        <div class="card">
            <div class="card__body">
                <form method="GET" action="{{ route('admin.reports') }}" class="flex flex-wrap gap-3 items-end">
                    <div>
                        <label for="status" class="text-sm font-medium text-muted-foreground">Status</label>
                        <select name="status" id="status" class="select">
                            <option value="">semua status</option>
                            <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>perlu ditindak</option>
                            <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>selesai</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="button button--primary">Filter</button>
                        @if (request()->has('status'))
                            <a href="{{ route('admin.reports') }}" class="button button--ghost">Reset</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        {{-- ─── TABEL ─── --}}
        <div class="card">
            <div class="card__header">
                <div class="card__heading">
                    <h2 class="card__title">
                        Daftar Laporan
                        <span class="badge badge--primary ms-2">{{ $reports->total() }}</span>
                    </h2>
                    <p class="card__subtitle">Ban IP memblokir semua pengiriman dari IP pengirim pesan.</p>
                </div>
            </div>
            <div class="card__body">
                @if ($reports->isEmpty())
                    <div class="empty-state empty-state--sm">
                        <p class="empty-state__title">Tidak ada laporan 🎉</p>
                        <p class="empty-state__text">Belum ada pengunjung yang melaporkan pesan.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table--hover table--sm">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Pesan</th>
                                    <th>IP Pengirim</th>
                                    <th>Alasan</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($reports as $report)
                                    <tr>
                                        <td class="text-muted-foreground text-xs whitespace-nowrap">{{ $report->created_at?->format('d M Y H:i') }}</td>
                                        <td>
                                            <p class="font-medium">
                                                {{ $report->message?->sender_name ?: 'Anonim' }}
                                                <span class="text-muted-foreground font-normal">→</span>
                                                {{ $report->message?->recipient_name }}
                                                <span class="badge badge--soft ms-1">{{ $report->message?->kelas }}</span>
                                            </p>
                                            <p class="text-xs text-muted-foreground mt-1" style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                                                title="{{ $report->message?->message }}">
                                                “{{ \Illuminate\Support\Str::limit($report->message?->message ?? '(pesan sudah dihapus)', 60) }}”
                                            </p>
                                        </td>
                                        <td>
                                            <code>{{ $report->message?->ip_address ?: '—' }}</code>
                                        </td>
                                        <td>
                                            @if ($report->reason)
                                                <span class="text-sm">{{ $report->reason }}</span>
                                            @else
                                                <span class="text-muted-foreground text-xs">tanpa alasan</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($report->is_resolved)
                                                <span class="badge badge--soft">selesai</span>
                                            @else
                                                <span class="badge badge--danger">perlu ditindak</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="flex items-center justify-end gap-1.5">
                                                {{-- Aksi utama (hanya untuk laporan yang belum selesai) --}}
                                                @if ($report->message && ! $report->is_resolved)
                                                    {{-- Tandai selesai --}}
                                                    <form method="POST" action="{{ route('admin.reports.resolve', $report) }}">
                                                        @csrf
                                                        <button type="submit" class="button button--outline button--sm"
                                                            title="Tandai laporan sudah ditangani">
                                                            ✓
                                                        </button>
                                                    </form>
                                                @endif

                                                {{-- Ban IP (aksi kecil — ikon saja, tooltip berisi IP) --}}
                                                @if ($report->message)
                                                    <form method="POST" action="{{ route('admin.reports.ban-ip', $report) }}"
                                                        data-confirm="Ban IP {{ $report->message->ip_address }}? Semua pengiriman dari IP ini akan diblokir."
                                                        data-confirm-ok="ya, ban"
                                                        data-confirm-title="Ban IP">
                                                        @csrf
                                                        <button type="submit" class="button button--soft button--sm button--icon-only"
                                                            aria-label="Ban IP {{ $report->message->ip_address }}"
                                                            title="Ban IP {{ $report->message->ip_address }}">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"
                                                                fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                                                stroke-linejoin="round" aria-hidden="true">
                                                                <circle cx="12" cy="12" r="9" />
                                                                <path d="M5.6 5.6l12.8 12.8" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                @endif

                                                {{-- Hapus pesan yang dilaporkan (ikon sampah langsung) --}}
                                                @if ($report->message)
                                                    <form method="POST" action="{{ route('admin.reports.delete-message', $report) }}"
                                                        data-confirm="Hapus pesan ini beserta semua balasannya? Tindakan ini tidak bisa dibatalkan."
                                                        data-confirm-ok="ya, hapus"
                                                        data-confirm-title="Hapus pesan">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="button button--danger button--sm button--icon-only"
                                                            aria-label="Hapus pesan yang dilaporkan" title="Hapus pesan yang dilaporkan">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                                                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                <path d="M4 7h16M9 7V4h6v3m2 0l-1 13a2 2 0 01-2 2h-4a2 2 0 01-2-2L7 7" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                @endif

                                                {{-- Hapus laporan (tanpa menghapus pesan) — ikon sampah langsung --}}
                                                <form method="POST" action="{{ route('admin.reports.destroy', $report) }}"
                                                    data-confirm="Hapus laporan ini? Pesannya TIDAK ikut terhapus."
                                                    data-confirm-ok="ya, hapus"
                                                    data-confirm-title="Hapus laporan">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="button button--ghost button--sm button--icon-only"
                                                        aria-label="Hapus laporan" title="Hapus laporan">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                                                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                            <path d="M4 7h16M9 7V4h6v3m2 0l-1 13a2 2 0 01-2 2h-4a2 2 0 01-2-2L7 7" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $reports->links('vendor.pagination.stisla') }}</div>
                @endif
            </div>
        </div>
    </section>
@endsection
