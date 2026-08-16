{{-- ─── DASHBOARD ADMIN — MERIDIAN / STISLA ─── --}}
@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
    {{-- ─── PAGE HEADER ─── --}}
    <section class="page">
        <div class="page__header">
            <div class="page__headline">
                <h1 class="page__title font-reenie">
                    Halo, <span>{{ auth()->user()->name }}</span> <span style="font-size:.65em">👋</span>
                </h1>
                <p class="page__description">Ringkasan pesan yang masuk ke SkanidaSong SMK.</p>
            </div>
            <div class="page__action">
                <a href="{{ url('/') }}" target="_blank" class="button button--outline">
                    <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                        stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 5h5v5M9 15L19 5M19 14v5H5V5h5" />
                    </svg>
                    Lihat Website
                </a>
            </div>
        </div>

        {{-- ─── ROW STATISTIK ─── --}}
        <div class="grid grid-cols-12 gap-4">
            {{-- Total Pesan --}}
            <div class="col-span-12 sm:col-span-6 xl:col-span-3">
                <div class="card card--stat">
                    <div class="card__body">
                        <div class="stat">
                            <p class="stat__label text-eyebrow">Total Pesan</p>
                            <p class="stat__value font-reenie">{{ number_format($totalMessages) }}</p>
                            <p class="stat__meta text-muted-foreground text-sm">sepanjang waktu</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pesan Hari Ini --}}
            <div class="col-span-12 sm:col-span-6 xl:col-span-3">
                <div class="card card--stat">
                    <div class="card__body">
                        <div class="stat">
                            <p class="stat__label text-eyebrow">Pesan Hari Ini</p>
                            <p class="stat__value font-reenie">{{ number_format($todayMessages) }}</p>
                            <p class="stat__meta text-muted-foreground text-sm">{{ \Carbon\Carbon::today()->format('d M Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pengirim Beridentitas --}}
            <div class="col-span-12 sm:col-span-6 xl:col-span-3">
                <div class="card card--stat">
                    <div class="card__body">
                        <div class="stat">
                            <p class="stat__label text-eyebrow">Pengirim Beridentitas</p>
                            <p class="stat__value font-reenie">{{ number_format($totalSenders) }}</p>
                            <p class="stat__meta text-muted-foreground text-sm">dari yang kirim nama</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kelas Terjangkau --}}
            <div class="col-span-12 sm:col-span-6 xl:col-span-3">
                <div class="card card--stat">
                    <div class="card__body">
                        <div class="stat">
                            <p class="stat__label text-eyebrow">Kelas Terjangkau</p>
                            <p class="stat__value font-reenie">{{ number_format($totalKelas) }}</p>
                            <p class="stat__meta text-muted-foreground text-sm">kelas unik</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── ROW: GRAFIK + KELAS TERPOPULER ─── --}}
        <div class="grid grid-cols-12 gap-6">
            {{-- Grafik pesan 14 hari --}}
            <div class="col-span-12 xl:col-span-8">
                <div class="card">
                    <div class="card__header">
                        <div class="card__heading">
                            <h2 class="card__title">Trend Pesan</h2>
                            <p class="card__subtitle">Jumlah pesan masuk 14 hari terakhir</p>
                        </div>
                    </div>
                    <div class="card__body">
                        <div class="chart" id="messagesChart" style="--chart-height: 300px;"></div>
                    </div>
                </div>
            </div>

            {{-- Kelas terpopuler --}}
            <div class="col-span-12 xl:col-span-4">
                <div class="card">
                    <div class="card__header">
                        <div class="card__heading">
                            <h2 class="card__title">Kelas Terpopuler</h2>
                            <p class="card__subtitle">Top 5 kelas penerima pesan</p>
                        </div>
                    </div>
                    <div class="card__body">
                        <div class="list-group list-group--block">
                            @foreach ($topKelas as $index => $k)
                                <div class="list-group__item">
                                    <div class="media">
                                        <span class="media__figure badge {{ $loop->first ? 'badge--primary' : 'badge--soft' }}">{{ $loop->iteration }}</span>
                                        <div class="media__content">
                                            <p class="media__title">{{ $k->kelas }}</p>
                                            <span class="media__meta">{{ $k->total }} pesan</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            @if ($topKelas->isEmpty())
                                <div class="list-group__item">
                                    <p class="media__title text-muted-foreground">Belum ada pesan masuk.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── ROW: TOP LAGU + STATISTIK LAGU ─── --}}
        <div class="grid grid-cols-12 gap-6">
            {{-- Top lagu --}}
            <div class="col-span-12 xl:col-span-8">
                <div class="card">
                    <div class="card__header">
                        <div class="card__heading">
                            <h2 class="card__title">Lagu Terpopuler</h2>
                            <p class="card__subtitle">Top 5 lagu yang paling sering didedikasikan</p>
                        </div>
                    </div>
                    <div class="card__body">
                        @if ($topSongs->isEmpty())
                            <div class="empty-state empty-state--sm">
                                <p class="empty-state__title">Belum ada lagu</p>
                                <p class="empty-state__text">Lagu yang dipilih pengirim bakal muncul di sini.</p>
                            </div>
                        @else
                            <div class="list-group list-group--block">
                                @foreach ($topSongs as $song)
                                    <div class="list-group__item">
                                        <div class="media">
                                            @if ($song->cover_url)
                                                <img src="{{ $song->cover_url }}"
                                                    style="width:44px;height:44px;border-radius:10px;flex-shrink:0"
                                                    class="object-cover" alt="cover">
                                            @else
                                                <span class="media__figure badge {{ $loop->first ? 'badge--primary' : 'badge--soft' }}">
                                                    {{ $loop->iteration }}
                                                </span>
                                            @endif
                                            <div class="media__content">
                                                <p class="media__title">{{ $song->song_title }}</p>
                                                <span class="media__meta">{{ $song->song_artist }} &bull; {{ $song->total }}x dikirim</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Statistik lagu --}}
            <div class="col-span-12 xl:col-span-4">
                <div class="card">
                    <div class="card__header">
                        <div class="card__heading">
                            <h2 class="card__title">Statistik Lagu</h2>
                            <p class="card__subtitle">Seberapa banyak pesan yang bawa lagu</p>
                        </div>
                    </div>
                    <div class="card__body">
                        <div class="grid grid-cols-12 gap-4 mb-4">
                            <div class="col-span-12 sm:col-span-6 xl:col-span-6">
                                <div class="card card--stat">
                                    <div class="card__body">
                                        <div class="stat">
                                            <p class="stat__label text-eyebrow">Dengan Lagu</p>
                                            <p class="stat__value font-reenie">{{ number_format($songsCount) }}</p>
                                            @php $songPct = $totalMessages > 0 ? round($songsCount / $totalMessages * 100) : 0; @endphp
                                            <p class="stat__meta text-muted-foreground text-sm">{{ $songPct }}% dari semua pesan</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-span-12 sm:col-span-6 xl:col-span-6">
                                <div class="card card--stat">
                                    <div class="card__body">
                                        <div class="stat">
                                            <p class="stat__label text-eyebrow">Tanpa Lagu</p>
                                            <p class="stat__value font-reenie">{{ number_format($noSongsCount) }}</p>
                                            <p class="stat__meta text-muted-foreground text-sm">pesan tanpa lagu</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="list-group list-group--block">
                            <div class="list-group__item">
                                <div class="media">
                                    <span class="media__figure badge badge--soft">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round" aria-hidden="true">
                                            <path d="M9 18V5l12-2v13" />
                                            <circle cx="6" cy="18" r="3" />
                                            <circle cx="18" cy="16" r="3" />
                                        </svg>
                                    </span>
                                    <div class="media__content">
                                        <p class="media__title">{{ number_format($uniqueSongs) }} lagu unik</p>
                                        <span class="media__meta">judul lagu berbeda yang dikirim</span>
                                    </div>
                                </div>
                            </div>
                            <div class="list-group__item">
                                <div class="media">
                                    <span class="media__figure badge badge--soft">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round" aria-hidden="true">
                                            <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </span>
                                    <div class="media__content">
                                        <p class="media__title">{{ number_format($uniqueArtists) }} artis unik</p>
                                        <span class="media__meta">penyanyi yang didedikasikan</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── PESAN TERBARU ─── --}}
        <div class="card">
            <div class="card__header">
                <div class="card__heading">
                    <h2 class="card__title">Pesan Terbaru</h2>
                    <p class="card__subtitle">5 pesan paling baru yang masuk</p>
                </div>
                <div class="card__action">
                    <a href="{{ route('admin.messages') }}" class="button button--ghost button--sm">Lihat semua</a>
                </div>
            </div>
            <div class="card__body">
                @if ($latestMessages->isEmpty())
                    <div class="empty-state empty-state--sm">
                        <p class="empty-state__title">Belum ada pesan</p>
                        <p class="empty-state__text">Pesan pertama yang dikirim bakal muncul di sini.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table--sm">
                            <thead>
                                <tr>
                                    <th>Dari</th>
                                    <th>IP</th>
                                    <th>Untuk</th>
                                    <th>Kelas</th>
                                    <th>Pesan</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($latestMessages as $msg)
                                    <tr>
                                        <td>
                                            @if ($msg->sender_name)
                                                <span class="badge badge--success">{{ $msg->sender_name }}</span>
                                            @else
                                                <span class="badge badge--soft">Anonim</span>
                                            @endif
                                        </td>
                                        <td class="text-muted-foreground text-xs">{{ $msg->ip_address ?: '—' }}</td>
                                        <td class="font-medium">{{ $msg->recipient_name }}</td>
                                        <td>{{ $msg->kelas }}</td>
                                        <td class="max-w-36">{!! \App\Support\EmojiText::small(\Illuminate\Support\Str::limit($msg->message, 40)) !!}</td>
                                        <td class="text-muted-foreground text-xs">{{ $msg->created_at->diffForHumans() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- ─── KELOLA STIKER (khusus admin) ─── --}}
        <div class="card">
            <div class="card__header">
                <div class="card__heading">
                    <h2 class="card__title">
                        Kelola Stiker 🎨
                        <span class="badge badge--primary ms-2">{{ $stickers->count() }}</span>
                    </h2>
                    <p class="card__subtitle">Unggah stiker yang bisa dipakai pengunjung di balasan pesan.</p>
                </div>
            </div>
            <div class="card__body">
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

                {{-- ─── FORM UNGGAH STIKER ─── --}}
                <form action="{{ route('admin.stickers.store') }}" method="POST" enctype="multipart/form-data"
                    class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="input-group" style="flex: 1 1 14rem; min-width: 0;">
                        <label for="sticker-name" class="input-group__label">Nama (opsional)</label>
                        <input type="text" name="name" id="sticker-name" maxlength="255"
                            placeholder="misal: love, teriak, senyum" class="input">
                    </div>
                    <div class="input-group" style="flex: 1 1 16rem; min-width: 0;">
                        <label for="sticker-file" class="input-group__label">File stiker</label>
                        <input type="file" name="sticker" id="sticker-file" accept="image/jpeg,image/png,image/webp"
                            required class="input">
                        <p class="text-xs text-muted-foreground mt-1.5">jpeg/png/webp, maks 2MB</p>
                    </div>
                    <button type="submit" class="button button--primary">Unggah</button>
                </form>

                @error('sticker')
                    <div class="alert alert--danger" style="margin-top: 0.75rem;">
                        <div class="alert__title">Stiker gagal disimpan</div>
                        <div class="alert__description">{{ $message }}</div>
                    </div>
                @enderror

                @if ($stickers->isEmpty())
                    <div class="empty-state empty-state--sm" style="margin-top: 1.5rem;">
                        <p class="empty-state__title">Belum ada stiker</p>
                        <p class="empty-state__text">Unggah stiker pertama — langsung tampil di picker balasan pesan.</p>
                    </div>
                @else
                    <div class="grid grid-cols-12 gap-4" style="margin-top: 1.5rem;">
                        @foreach ($stickers as $sticker)
                            <div class="card card--stat col-span-6 sm:col-span-4 lg:col-span-3 xl:col-span-2" style="margin: 0;">
                                <div class="card__body">
                                    <div class="flex items-center justify-between" style="gap: 8px; margin-bottom: 0.6rem;">
                                        <span class="badge badge--soft">{{ $loop->iteration }}</span>
                                        <form action="{{ route('admin.stickers.destroy', $sticker) }}" method="POST"
                                            data-confirm="Hapus stiker ini?"
                                            data-confirm-ok="ya, hapus"
                                            data-confirm-title="Hapus stiker">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="button button--danger button--sm button--icon-only"
                                                aria-label="Hapus stiker" title="Hapus stiker">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                                                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                    <img src="{{ $sticker->url() }}" alt="{{ $sticker->name ?: 'stiker' }}" loading="lazy"
                                        class="w-full aspect-square object-contain rounded-lg mb-2"
                                        style="background: var(--color-surface); border: 1px solid var(--color-border);">
                                    <p class="font-medium text-sm truncate" title="{{ $sticker->name ?: 'stiker' }}">
                                        {{ $sticker->name ?: 'stiker' }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- ─── PENGATURAN FITUR (khusus admin) ─── --}}
        <div class="card">
            <div class="card__header">
                <div class="card__heading">
                    <h2 class="card__title">
                        Pengaturan Fitur 📸
                        @if (\App\Support\Settings::photosEnabled())
                            <span class="badge badge--success ms-2">Aktif</span>
                        @else
                            <span class="badge badge--soft ms-2">Nonaktif</span>
                        @endif
                    </h2>
                    <p class="card__subtitle">Kendalikan fitur foto kamera di pesan publik.</p>
                </div>
            </div>
            <div class="card__body">
                @if (session('success'))
                    <div class="alert alert--success">
                        <div class="alert__title">Berhasil</div>
                        <div class="alert__description">{{ session('success') }}</div>
                    </div>
                @endif

                @if (\App\Support\Settings::photosEnabled())
                    <div class="alert alert--info" style="margin-bottom: 1rem;">
                        <div class="alert__title">Fitur foto aktif</div>
                        <div class="alert__description">Foto jepretan kamera tampil di pesan dan menu kamera tersedia di form kirim pesan.</div>
                    </div>
                @else
                    <div class="alert alert--danger" style="margin-bottom: 1rem;">
                        <div class="alert__title">Fitur foto nonaktif</div>
                        <div class="alert__description">Foto disembunyikan dari semua pesan publik dan menu kamera dihapus dari form kirim. Foto lama tetap tersimpan dan tetap terlihat di panel Pesan Masuk untuk moderasi.</div>
                    </div>
                @endif

                <form action="{{ route('admin.settings.photo-toggle') }}" method="POST">
                    @csrf
                    @if (\App\Support\Settings::photosEnabled())
                        <button type="submit" class="button button--danger">Nonaktifkan Fitur Foto</button>
                    @else
                        <button type="submit" class="button button--primary">Aktifkan Fitur Foto</button>
                    @endif
                </form>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    {{-- ApexCharts dari CDN — sama kayak charts.js template --}}
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        (function () {
            var el = document.getElementById('messagesChart');
            if (!el || !window.ApexCharts) return;

            var root = document.documentElement;
            function token(name) {
                return getComputedStyle(root).getPropertyValue(name).trim();
            }
            function mode() {
                return root.dataset.theme === 'dark' ? 'dark' : 'light';
            }

            var chart = new ApexCharts(el, {
                chart: {
                    type: 'area',
                    height: '100%',
                    fontFamily: 'inherit',
                    toolbar: { show: false },
                    zoom: { enabled: false },
                    background: 'transparent',
                },
                theme: { mode: mode() },
                colors: [token('--color-primary')],
                series: [{
                    name: 'Pesan',
                    data: @json($chartData->values()->all()),
                }],
                xaxis: {
                    categories: @json($chartLabels->values()->all()),
                    axisBorder: { color: token('--color-border') },
                    axisTicks: { color: token('--color-border') },
                    labels: { style: { colors: token('--color-muted-foreground') } },
                },
                yaxis: {
                    labels: { style: { colors: token('--color-muted-foreground') } },
                },
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 2 },
                grid: { borderColor: token('--color-border'), strokeDashArray: 4 },
                tooltip: { theme: mode() },
            });
            chart.render();

            window.addEventListener('stisla:themechange', function () {
                chart.updateOptions({
                    theme: { mode: mode() },
                    colors: [token('--color-primary')],
                    xaxis: {
                        axisBorder: { color: token('--color-border') },
                        axisTicks: { color: token('--color-border') },
                        labels: { style: { colors: token('--color-muted-foreground') } },
                    },
                    yaxis: { labels: { style: { colors: token('--color-muted-foreground') } } },
                    grid: { borderColor: token('--color-border'), strokeDashArray: 4 },
                    tooltip: { theme: mode() },
                });
            });
        })();
    </script>
@endpush