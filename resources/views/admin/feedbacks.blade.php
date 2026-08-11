{{-- ─── HALAMAN SARAN & KRITIK ─── --}}
{{-- Semua masukan pengunjung (saran & kritik) yang dikirim lewat modal setelah kirim story. --}}
@extends('admin.layouts.app')

@section('title', 'Saran & Kritik')

@section('content')
    <section class="page">
        <div class="page__header">
            <div class="page__headline">
                <h1 class="page__title font-reenie">Saran &amp; Kritik 💌</h1>
                <p class="page__description">
                    Masukan pengunjung yang dikirim setelah mereka berhasil mengirim story —
                    saran dan kritik untuk membuat SkanidaSong lebih baik.
                </p>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert--success">
                <div class="alert__title">Berhasil</div>
                <div class="alert__description">{{ session('success') }}</div>
            </div>
        @endif

        {{-- ─── ROW STATISTIK ─── --}}
        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 sm:col-span-4">
                <div class="card card--stat">
                    <div class="card__body">
                        <div class="stat">
                            <p class="stat__label text-eyebrow">Total Masukan</p>
                            <p class="stat__value font-reenie">{{ number_format($stats['total']) }}</p>
                            <p class="stat__meta text-muted-foreground text-sm">semua saran &amp; kritik</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-span-12 sm:col-span-4">
                <div class="card card--stat">
                    <div class="card__body">
                        <div class="stat">
                            <p class="stat__label text-eyebrow">Hari Ini</p>
                            <p class="stat__value font-reenie">{{ number_format($stats['today']) }}</p>
                            <p class="stat__meta text-muted-foreground text-sm">{{ \Carbon\Carbon::today()->format('d M') }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-span-12 sm:col-span-4">
                <div class="card card--stat">
                    <div class="card__body">
                        <div class="stat">
                            <p class="stat__label text-eyebrow">Berisi Saran</p>
                            <p class="stat__value font-reenie">{{ number_format($stats['withSaran']) }}</p>
                            <p class="stat__meta text-muted-foreground text-sm">dengan kolom saran terisi</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── SEARCH ─── --}}
        <div class="card">
            <div class="card__body">
                <form method="GET" action="{{ route('admin.feedbacks') }}" class="flex flex-wrap gap-3 items-end mb-4">
                    <div class="input-group input-group--search" style="flex: 1 1 16rem; min-width: 0;">
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="cari saran / kritik / IP..." class="input">
                    </div>
                    <button type="submit" class="button button--primary">Cari</button>
                    @if (request()->filled('search'))
                        <a href="{{ route('admin.feedbacks') }}" class="button button--ghost">Reset</a>
                    @endif
                </form>

                {{-- ─── TABEL ─── --}}
                <div class="table-responsive">
                    <table class="table table--hover table--sm">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Saran 💡</th>
                                <th>Kritik ✍️</th>
                                <th>IP</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($feedbacks as $fb)
                                <tr>
                                    <td class="text-muted-foreground text-xs whitespace-nowrap">{{ $fb->created_at?->format('d M Y H:i') }}</td>
                                    <td class="text-sm" style="max-width:280px">
                                        @if ($fb->saran)
                                            <span class="text-sm">{{ $fb->saran }}</span>
                                        @else
                                            <span class="text-muted-foreground text-xs">—</span>
                                        @endif
                                    </td>
                                    <td class="text-sm" style="max-width:280px">
                                        @if ($fb->kritik)
                                            <span class="text-sm">{{ $fb->kritik }}</span>
                                        @else
                                            <span class="text-muted-foreground text-xs">—</span>
                                        @endif
                                    </td>
                                    <td><code class="text-xs">{{ $fb->ip_address ?: '—' }}</code></td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('admin.feedbacks.destroy', $fb) }}"
                                            data-confirm="Hapus masukan ini? Tindakan ini tidak bisa dibatalkan."
                                            data-confirm-ok="ya, hapus"
                                            data-confirm-title="Hapus masukan">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="button button--danger button--sm button--icon-only"
                                                aria-label="Hapus masukan" title="Hapus masukan">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                                                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted-foreground py-6">Belum ada saran &amp; kritik dari pengunjung.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $feedbacks->appends(request()->query())->links('vendor.pagination.stisla') }}</div>
            </div>
        </div>
    </section>
@endsection
