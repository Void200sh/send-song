{{-- ─── DASHBOARD ADMIN — MERIDIAN / STISLA ─── --}}
@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
    {{-- ─── PAGE HEADER ─── --}}
    <section class="page">
        <div class="page__header">
            <div class="page__headline">
                <h1 class="page__title font-reenie">
                    Halo, <span>{{ auth()->user()->name }}</span> 👋
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
                                        <td class="font-medium">{{ $msg->recipient_name }}</td>
                                        <td>{{ $msg->kelas }}</td>
                                        <td class="max-w-36">{{ \Illuminate\Support\Str::limit($msg->message, 40) }}</td>
                                        <td class="text-muted-foreground text-xs">{{ $msg->created_at->diffForHumans() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
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