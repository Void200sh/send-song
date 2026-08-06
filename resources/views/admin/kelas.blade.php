{{-- ─── HALAMAN REKAP PER KELAS ADMIN ─── --}}
@extends('admin.layouts.app')

@section('title', 'Kelas')

@section('content')
    {{-- ─── PAGE HEADER ─── --}}
    <section class="page">
        <div class="page__header">
            <div class="page__headline">
                <h1 class="page__title font-reenie">Kelas</h1>
                <p class="page__description">Rekap pesan & lagu per kelas — biar keliatan kelas mana yang paling rame.</p>
            </div>
            <div class="page__action">
                <a href="{{ route('admin.dashboard') }}" class="button button--outline button--sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                        stroke-linejoin="round" aria-hidden="true">
                        <path d="M15 6l-6 6 6 6" />
                    </svg>
                    Kembali
                </a>
            </div>
        </div>

        {{-- ─── CARD GRID KELAS ─── --}}
        <div class="card">
            <div class="card__header">
                <div class="card__heading">
                    <h2 class="card__title">
                        Rekap Kelas
                        <span class="badge badge--primary ms-2">{{ $kelas->count() }}</span>
                    </h2>
                    <p class="card__subtitle">Klik nama kelas untuk lihat pesan-pesannya.</p>
                </div>
            </div>

            <div class="card__body">
                @if ($kelas->isEmpty())
                    {{-- ─── KONDISI KOSONG ─── --}}
                    <div class="empty-state">
                        <div class="empty-state__text">
                            <h3 class="empty-state__title">Belum ada kelas</h3>
                            <p class="empty-state__text">Belum ada pesan yang masuk sama sekali.</p>
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-12 gap-4">
                        @foreach ($kelas as $k)
                            <div class="card card--stat col-span-12 sm:col-span-6 xl:col-span-4"
                                style="margin: 0;">
                                <div class="card__header" style="border: 0; padding: 1.25rem 1.25rem 0;">
                                    <div class="card__heading">
                                        <h3 class="card__title" style="font-size: 1.1rem;">{{ $k->kelas }}</h3>
                                        <p class="card__subtitle text-xs">
                                            Aktif sejak {{ $k->first_at ? \Illuminate\Support\Carbon::parse($k->first_at)->format('d M Y') : '—' }}
                                        </p>
                                    </div>
                                    <span class="badge badge--primary">{{ $k->total }} pesan</span>
                                </div>
                                <div class="card__body">
                                    <div class="grid grid-cols-3 gap-2">
                                        <div class="stat">
                                            <div class="stat__value text-muted-foreground">{{ $k->total }}</div>
                                            <div class="stat__label">Pesan</div>
                                        </div>
                                        <div class="stat">
                                            <div class="stat__value">{{ $k->with_song }}</div>
                                            <div class="stat__label">Dengan Lagu</div>
                                        </div>
                                        <div class="stat">
                                            <div class="stat__value text-muted-foreground">{{ $k->unique_songs }}</div>
                                            <div class="stat__label">Lagu Unik</div>
                                        </div>
                                    </div>
                                    <div class="flex flex-col" style="gap: 8px; margin-top: 1rem;">
                                        <a href="{{ route('admin.messages', ['kelas' => $k->kelas]) }}"
                                            class="button button--primary button--sm w-full">Lihat Pesan Kelas Ini</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
