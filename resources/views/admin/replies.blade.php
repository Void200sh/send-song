{{-- ─── HALAMAN BALASAN (KOMENTAR) ADMIN — PANTAU KOMENTAR DI SEMUA PESAN ─── --}}
@extends('admin.layouts.app')

@section('title', 'Balasan')

@section('content')
    <section class="page">
        <div class="page__header">
            <div class="page__headline">
                <h1 class="page__title font-reenie">Balasan 💬</h1>
                <p class="page__description">
                    Semua balasan/komentar di setiap pesan — diawasi agar tidak ada komentar nyeleneh.
                </p>
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

        @if (session('success'))
            <div class="alert alert--success">
                <div class="alert__title">Berhasil</div>
                <div class="alert__description">{{ session('success') }}</div>
            </div>
        @endif

        {{-- ─── ROW STATISTIK ─── --}}
        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 sm:col-span-6 xl:col-span-4">
                <div class="card card--stat">
                    <div class="card__body">
                        <div class="stat">
                            <p class="stat__label text-eyebrow">Total Balasan</p>
                            <p class="stat__value font-reenie">{{ number_format($stats['total']) }}</p>
                            <p class="stat__meta text-muted-foreground text-sm">komentar di semua pesan</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-span-12 sm:col-span-6 xl:col-span-4">
                <div class="card card--stat">
                    <div class="card__body">
                        <div class="stat">
                            <p class="stat__label text-eyebrow">Hari Ini</p>
                            <p class="stat__value font-reenie">{{ number_format($stats['today']) }}</p>
                            <p class="stat__meta text-muted-foreground text-sm">{{ \Carbon\Carbon::today()->format('d M Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-span-12 sm:col-span-6 xl:col-span-4">
                <div class="card card--stat">
                    <div class="card__body">
                        <div class="stat">
                            <p class="stat__label text-eyebrow">IP Unik</p>
                            <p class="stat__value font-reenie">{{ number_format($stats['uniqueIps']) }}</p>
                            <p class="stat__meta text-muted-foreground text-sm">penulis balasan berbeda</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── TABEL ─── --}}
        <div class="card">
            <div class="card__header">
                <div class="card__heading">
                    <h2 class="card__title">
                        Daftar Balasan
                        <span class="badge badge--primary ms-2">{{ $replies->total() }}</span>
                    </h2>
                    <p class="card__subtitle">Klik tombol hapus untuk menghapus komentar nyeleneh.</p>
                </div>
            </div>
            <div class="card__body">
                {{-- ─── SEARCH ─── --}}
                <form method="GET" action="{{ route('admin.replies') }}" class="flex flex-wrap gap-3 items-end">
                    <div class="input-group input-group--search" style="flex: 1 1 16rem; min-width: 0;">
                        <input type="text" name="search" id="search" value="{{ request('search') }}"
                            placeholder="cari nama / isi balasan / IP..." class="input">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="button button--primary">Cari</button>
                        @if (request()->has('search'))
                            <a href="{{ route('admin.replies') }}" class="button button--ghost">Reset</a>
                        @endif
                    </div>
                </form>

                @if ($replies->isEmpty())
                    <div class="empty-state empty-state--sm">
                        <p class="empty-state__title">Belum ada balasan</p>
                        <p class="empty-state__text">Tidak ada komentar yang cocok dengan pencarianmu.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table--hover table--sm">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Dari</th>
                                    <th>Isi Balasan</th>
                                    <th>Pada Pesan</th>
                                    <th>IP</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($replies as $reply)
                                    <tr>
                                        <td class="text-muted-foreground text-xs whitespace-nowrap">{{ $reply->created_at?->format('d M Y H:i') }}</td>
                                        <td>
                                            @if ($reply->sender_name)
                                                <span class="font-medium">{{ $reply->sender_name }}</span>
                                            @else
                                                <span class="text-muted-foreground">Anonim</span>
                                            @endif
                                            @if ($reply->parent)
                                                <span class="block text-[11px] text-muted-foreground mt-0.5">
                                                    membalas {{ '@' . ($reply->parent->sender_name ?: 'anonim') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="max-w-48">
                                            @if ($reply->sticker_path)
                                                <img src="{{ asset('storage/' . $reply->sticker_path) }}" alt="stiker"
                                                    loading="lazy" class="w-10 h-10 object-contain rounded-lg border border-[var(--color-border)] mb-1">
                                            @endif
                                            @if ($reply->body)
                                                <p class="text-sm" style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                                                    title="{{ $reply->body }}">
                                                    {!! \App\Support\EmojiText::small(\Illuminate\Support\Str::limit($reply->body, 60)) !!}
                                                </p>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($reply->message)
                                                <p class="font-medium">
                                                    {{ $reply->message->sender_name ?: 'Anonim' }}
                                                    <span class="text-muted-foreground font-normal">→</span>
                                                    {{ $reply->message->recipient_name }}
                                                </p>
                                                <a href="{{ route('messages.show', $reply->message) }}" target="_blank"
                                                    class="text-xs font-medium" style="color:#2563eb">
                                                    buka pesan →
                                                </a>
                                            @else
                                                <span class="text-muted-foreground text-xs">(pesan sudah dihapus)</span>
                                            @endif
                                        </td>
                                        <td><code>{{ $reply->ip_address ?: '—' }}</code></td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('admin.replies.destroy', $reply) }}"
                                                data-confirm="Hapus balasan ini?"
                                                data-confirm-ok="ya, hapus"
                                                data-confirm-title="Hapus balasan">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="button button--danger button--sm button--icon-only"
                                                    aria-label="Hapus balasan" title="Hapus balasan">
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
                    <div class="mt-4">{{ $replies->links('vendor.pagination.stisla') }}</div>
                @endif
            </div>
        </div>
    </section>
@endsection
