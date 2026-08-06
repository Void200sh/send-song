{{-- ─── HALAMAN EXPORT DATA ADMIN ─── --}}
@extends('admin.layouts.app')

@section('title', 'Export Data')

@section('content')
    {{-- ─── PAGE HEADER ─── --}}
    <section class="page">
        <div class="page__header">
            <div class="page__headline">
                <h1 class="page__title font-reenie">Export Data</h1>
                <p class="page__description">Unduh data pesan & lagu sebagai file CSV — buka langsung di Excel / Google Sheets.</p>
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

        {{-- ─── CARD EXPORT ─── --}}
        <div class="card">
            <div class="card__header">
                <div class="card__heading">
                    <h2 class="card__title">Unduh Data</h2>
                    <p class="card__subtitle">Kosongin rentang tanggal untuk ambil semua data.</p>
                </div>
            </div>

            <div class="card__body">
                {{-- ─── FORM RENTANG TANGGAL ─── --}}
                <form method="GET" action="{{ route('admin.export') }}" class="flex flex-wrap gap-3">
                    <div class="flex gap-2">
                        <div class="input-group">
                            <label for="from" class="input-group__label">Dari tanggal</label>
                            <input type="date" name="from" id="from" value="{{ request('from') }}" class="input">
                        </div>
                        <div class="input-group">
                            <label for="to" class="input-group__label">Sampai tanggal</label>
                            <input type="date" name="to" id="to" value="{{ request('to') }}" class="input">
                        </div>
                        <button type="submit" class="button button--primary" style="align-self: flex-end;">Terapkan</button>
                        @if (request()->filled('from') || request()->filled('to'))
                            <a href="{{ route('admin.export') }}" class="button button--ghost" style="align-self: flex-end;">Reset</a>
                        @endif
                    </div>
                </form>

                {{-- ─── TOMBOL DOWNLOAD ─── --}}
                <div class="grid grid-cols-12 gap-4" style="margin-top: 1.5rem;">
                    <div class="card card--stat col-span-12 sm:col-span-6" style="margin: 0;">
                        <div class="card__body">
                            <div class="flex items-center" style="gap: 12px; margin-bottom: 0.75rem;">
                                <span class="badge badge--primary" style="font-size: 20px;">💬</span>
                                <div>
                                    <h3 class="card__title" style="font-size: 1rem; margin: 0;">Export Pesan</h3>
                                    <p class="card__subtitle text-xs">ID, pengirim, penerima, kelas, isi pesan, & info lagu.</p>
                                </div>
                            </div>
                            <a href="{{ route('admin.export.messages', request()->only(['from', 'to'])) }}"
                                class="button button--primary button--sm w-full">Unduh CSV Pesan</a>
                        </div>
                    </div>

                    <div class="card card--stat col-span-12 sm:col-span-6" style="margin: 0;">
                        <div class="card__body">
                            <div class="flex items-center" style="gap: 12px; margin-bottom: 0.75rem;">
                                <span class="badge badge--primary" style="font-size: 20px;">🎵</span>
                                <div>
                                    <h3 class="card__title" style="font-size: 1rem; margin: 0;">Export Lagu</h3>
                                    <p class="card__subtitle text-xs">Judul, artis, berapa kali dikirim, terakhir dipakai.</p>
                                </div>
                            </div>
                            <a href="{{ route('admin.export.songs', request()->only(['from', 'to'])) }}"
                                class="button button--outline button--sm w-full">Unduh CSV Lagu</a>
                        </div>
                    </div>
                </div>

                <p class="text-muted-foreground text-sm" style="margin-top: 1.5rem;">
                    Catatan: file CSV bisa dibuka di Microsoft Excel, Google Sheets, atau Numbers. Pastikan memilih
                    delimiter koma (,) saat membuka di Excel.
                </p>
            </div>
        </div>
    </section>
@endsection
