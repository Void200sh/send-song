{{-- ─── HALAMAN PESAN ADMIN — LIHAT SIAPA YANG KIRIM ─── --}}
@extends('admin.layouts.app')

@section('title', 'Pesan Masuk')

@section('content')
    {{-- ─── PAGE HEADER ─── --}}
    <section class="page">
        <div class="page__header">
            <div class="page__headline">
                <h1 class="page__title font-reenie">Pesan Masuk</h1>
                <p class="page__description">Semua pesan yang dikirim — lengkap dengan siapa pengirimnya.</p>
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

        {{-- ─── FLASH MESSAGE ─── --}}
        @if (session('success'))
            <div class="alert alert--success">
                <div class="alert__title">Berhasil!</div>
                <div class="alert__description">{{ session('success') }}</div>
            </div>
        @endif

        {{-- ─── CARD TABEL PESAN ─── --}}
        <div class="card">
            <div class="card__header">
                <div class="card__heading">
                    <h2 class="card__title">
                        Daftar Pesan
                        <span class="badge badge--primary ms-2">{{ $messages->total() }}</span>
                    </h2>
                    <p class="card__subtitle">Klik tombol hapus untuk menghapus pesan.</p>
                </div>
            </div>

            <div class="card__body">
                {{-- ─── SEARCH & FILTER ─── --}}
                <form method="GET" action="{{ route('admin.messages') }}" class="flex flex-wrap gap-3">
                    <div class="input-group input-group--search" style="flex: 1 1 16rem; min-width: 0;">
                        <input type="text" name="search" id="search" value="{{ request('search') }}"
                            placeholder="cari nama pengirim / penerima..."
                            class="input">
                    </div>
                    <div class="flex gap-2">
                        <select name="kelas" id="kelas" class="select">
                            <option value="">semua kelas</option>
                            @foreach ($kelasList as $k)
                                <option value="{{ $k }}" {{ request('kelas') == $k ? 'selected' : '' }}>{{ $k }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="button button--primary">Filter</button>
                        @if (request()->has('search') || request()->has('kelas'))
                            <a href="{{ route('admin.messages') }}" class="button button--ghost">Reset</a>
                        @endif
                    </div>
                </form>

                @if ($messages->isEmpty())
                    {{-- ─── KONDISI KOSONG ─── --}}
                    <div class="empty-state">
                        <div class="empty-state__text">
                            <h3 class="empty-state__title">Belum ada pesan</h3>
                            <p class="empty-state__text">Tidak ada pesan yang cocok dengan filtermu.</p>
                        </div>
                    </div>
                @else
                    {{-- ─── TABEL ─── --}}
                    <div class="table-responsive">
                        <table class="table table--hover table--sm">
                            <thead>
                                <tr>
                                    <th>Dari &bull; Pengirim</th>
                                    <th>Untuk</th>
                                    <th>Kelas</th>
                                    <th>Isi Pesan</th>
                                    <th>Lagu</th>
                                    <th>Waktu</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($messages as $msg)
                                    <tr>
                                        <td>
                                            @if ($msg->sender_name)
                                                <span class="font-medium">{{ $msg->sender_name }}</span>
                                            @else
                                                <span class="text-muted-foreground">Anonim</span>
                                            @endif
                                        </td>
                                        <td class="font-medium">{{ $msg->recipient_name }}</td>
                                        <td>{{ $msg->kelas }}</td>
                                        <td class="max-w-36">{{ $msg->message }}</td>
                                        <td>
                                            @if ($msg->spotify_track_id)
                                                <a href="https://open.spotify.com/track/{{ $msg->spotify_track_id }}" target="_blank"
                                                    class="font-medium text-[#1DB954] hover:underline">
                                                    Spotify
                                                </a>
                                            @else
                                                <span class="text-muted-foreground text-xs">—</span>
                                            @endif
                                        </td>
                                        <td class="text-muted-foreground text-xs">{{ $msg->created_at->format('d M Y H:i') }}</td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('admin.messages.destroy', $msg) }}"
                                                onsubmit="return confirm('Yakin mau hapus pesan ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="button button--danger button--sm button--icon-only"
                                                    aria-label="Hapus pesan">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                                                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M4 7h16M9 7V4h6v3m2 0l-1 13a2 2 0 01-2 2h-4a2 2 0 01-2-2L7 7" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- ─── PAGINATION ─── --}}
                    <div class="mt-4">
                        {{ $messages->links('vendor.pagination.stisla') }}
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection