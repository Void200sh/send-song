{{-- ─── HALAMAN PERPUSTAKAAN LAGU ADMIN ─── --}}
@extends('admin.layouts.app')

@section('title', 'Lagu')

@section('content')
    {{-- ─── PAGE HEADER ─── --}}
    <section class="page">
        <div class="page__header">
            <div class="page__headline">
                <h1 class="page__title font-reenie">Lagu</h1>
                <p class="page__description">Semua lagu yang pernah didedikasikan lewat pesan — berapa kali dipakai & link putarnya.</p>
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

        {{-- ─── CARD DAFTAR LAGU ─── --}}
        <div class="card">
            <div class="card__header">
                <div class="card__heading">
                    <h2 class="card__title">
                        Perpustakaan Lagu
                        <span class="badge badge--primary ms-2">{{ $songs->total() }}</span>
                    </h2>
                    <p class="card__subtitle">Lagu dikelompokkan berdasarkan judul & artis.</p>
                </div>
            </div>

            <div class="card__body">
                {{-- ─── SEARCH & FILTER ─── --}}
                <form method="GET" action="{{ route('admin.songs') }}" class="flex flex-wrap gap-3">
                    <div class="input-group input-group--search" style="flex: 1 1 16rem; min-width: 0;">
                        <input type="text" name="search" id="search" value="{{ request('search') }}"
                            placeholder="cari judul / artis..."
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
                            <a href="{{ route('admin.songs') }}" class="button button--ghost">Reset</a>
                        @endif
                    </div>
                </form>

                @if ($songs->isEmpty())
                    {{-- ─── KONDISI KOSONG ─── --}}
                    <div class="empty-state">
                        <div class="empty-state__text">
                            <h3 class="empty-state__title">Belum ada lagu</h3>
                            <p class="empty-state__text">Tidak ada lagu yang cocok dengan filtermu.</p>
                        </div>
                    </div>
                @else
                    {{-- ─── TABEL ─── --}}
                    <div class="table-responsive">
                        <table class="table table--hover table--sm">
                            <thead>
                                <tr>
                                    <th>Lagu</th>
                                    <th>Artis</th>
                                    <th>Dikirim</th>
                                    <th>Terakhir Dipakai</th>
                                    <th class="text-end">Putar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($songs as $song)
                                    @php
                                        $key = strtolower($song->song_title) . '|' . strtolower($song->song_artist);
                                        $link = $links->get($key, ['youtube' => null, 'spotify' => null]);
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="flex items-center" style="gap: 12px;">
                                                @if ($song->cover_url)
                                                    <img src="{{ $song->cover_url }}" alt="cover {{ $song->song_title }}"
                                                        loading="lazy"
                                                        style="width: 44px; height: 44px; border-radius: 10px; object-fit: cover; flex-shrink: 0;">
                                                @else
                                                    <div class="badge badge--soft"
                                                        style="width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 18px;">
                                                        {{ strtoupper(mb_substr($song->song_title, 0, 1)) }}
                                                    </div>
                                                @endif
                                                <span style="max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                                                    class="font-medium">{{ $song->song_title }}</span>
                                            </div>
                                        </td>
                                        <td class="text-muted-foreground text-sm">{{ $song->song_artist ?: '—' }}</td>
                                        <td><span class="badge badge--primary">{{ $song->total }}x</span></td>
                                        <td class="text-muted-foreground text-xs">{{ $song->last_used_at ? \Illuminate\Support\Carbon::parse($song->last_used_at)->format('d M Y H:i') : '—' }}</td>
                                        <td class="text-end">
                                            <div class="flex" style="gap: 8px; justify-content: flex-end;">
                                                @if (!empty($link['youtube']))
                                                    <a href="https://www.youtube.com/watch?v={{ $link['youtube'] }}" target="_blank"
                                                        class="button button--outline button--sm button--icon-only"
                                                        aria-label="Putar di YouTube" title="Putar di YouTube">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                            viewBox="0 0 24 24" fill="currentColor" stroke="none" aria-hidden="true">
                                                            <path d="M8 5.14v13.72c0 .81.87 1.31 1.57.9l11.02-6.86c.65-.4.65-1.4 0-1.8L9.57 4.24c-.7-.41-1.57.09-1.57.9z" />
                                                        </svg>
                                                    </a>
                                                @endif
                                                @if (!empty($link['spotify']))
                                                    <a href="https://open.spotify.com/track/{{ $link['spotify'] }}" target="_blank"
                                                        class="button button--outline button--sm button--icon-only"
                                                        aria-label="Putar di Spotify" title="Putar di Spotify">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                            viewBox="0 0 24 24" fill="currentColor" stroke="none" aria-hidden="true">
                                                            <path d="M12 2a10 10 0 100 20 10 10 0 000-20zm4.59 14.42a.62.62 0 01-.86.21c-2.36-1.44-5.33-1.77-8.83-.97a.62.62 0 11-.26-1.22c3.77-.86 7-.49 9.6 1.12.3.18.39.56.2.86zm1.22-2.72a.78.78 0 01-1.07.26c-2.7-1.66-6.82-2.14-10.02-1.17a.78.78 0 11-.45-1.49c3.55-1.08 8.05-.55 11.1 1.34.37.22.48.7.26 1.06zm.1-2.83c-3.24-1.92-8.58-2.1-11.67-1.16a.94.94 0 11-.54-1.79c3.5-1.06 9.33-.85 13 1.38a.94.94 0 01-1.05 1.57h.26z" />
                                                        </svg>
                                                    </a>
                                                @endif
                                                @if (empty($link['youtube']) && empty($link['spotify']))
                                                    <span class="badge badge--warning">belum ada link</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- ─── PAGINATION ─── --}}
                    <div class="mt-4">
                        {{ $songs->links('vendor.pagination.stisla') }}
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
