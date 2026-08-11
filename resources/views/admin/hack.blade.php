{{-- ─── HALAMAN JEJAK HACKING ─── --}}
@extends('admin.layouts.app')

@section('title', 'Jejak Hacking')

@section('content')
    <section class="page">
        <div class="page__header">
            <div class="page__headline">
                <h1 class="page__title font-reenie">Jejak Hacking 🛡️</h1>
                <p class="page__description">
                    Percobaan mencurigakan dari luar (injeksi SQL/XSS, probing file sensitif, brute-force login)
                    terekam otomatis di sini.
                </p>
            </div>
            <div class="page__action" style="display:flex;gap:8px;flex-wrap:wrap">
                <form method="POST" action="{{ route('admin.hack.read-all') }}">
                    @csrf
                    <button type="submit" class="button button--outline button--sm">Tandai dibaca</button>
                </form>
                <form method="POST" action="{{ route('admin.hack.clear') }}"
                    data-confirm="Hapus SEMUA jejak hacking? Tindakan ini tidak bisa dibatalkan."
                    data-confirm-ok="ya, hapus semua"
                    data-confirm-title="Hapus semua jejak">
                    @csrf
                    <button type="submit" class="button button--danger button--sm">Hapus semua</button>
                </form>
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
            <div class="col-span-12 sm:col-span-6 xl:col-span-3">
                <div class="card card--stat">
                    <div class="card__body">
                        <div class="stat">
                            <p class="stat__label text-eyebrow">Total Jejak</p>
                            <p class="stat__value font-reenie">{{ number_format($stats['total']) }}</p>
                            <p class="stat__meta text-muted-foreground text-sm">percobaan terdeteksi</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-span-12 sm:col-span-6 xl:col-span-3">
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
            <div class="col-span-12 sm:col-span-6 xl:col-span-3">
                <div class="card card--stat">
                    <div class="card__body">
                        <div class="stat">
                            <p class="stat__label text-eyebrow">Serangan Kritis</p>
                            <p class="stat__value font-reenie" style="color:{{ $stats['critical'] > 0 ? '#dc2626' : 'inherit' }}">{{ number_format($stats['critical']) }}</p>
                            <p class="stat__meta text-muted-foreground text-sm">command / code injection</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-span-12 sm:col-span-6 xl:col-span-3">
                <div class="card card--stat">
                    <div class="card__body">
                        <div class="stat">
                            <p class="stat__label text-eyebrow">IP Unik</p>
                            <p class="stat__value font-reenie">{{ number_format($stats['uniqueIps']) }}</p>
                            <p class="stat__meta text-muted-foreground text-sm">sumber berbeda</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── FILTER ─── --}}
        <div class="card">
            <div class="card__body">
                <form method="GET" action="{{ route('admin.hack') }}" class="flex flex-wrap gap-3 items-end">
                    <div>
                        <label for="severity" class="text-sm font-medium text-muted-foreground">Severitas</label>
                        <select name="severity" id="severity" class="select">
                            <option value="">semua severitas</option>
                            @foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $value => $label)
                                <option value="{{ $value }}" {{ request('severity') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="input-group input-group--search" style="flex: 1 1 16rem; min-width: 0;">
                        <input type="search" name="search" id="search" value="{{ request('search') }}"
                            placeholder="cari IP / path / alasan..." class="input">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="button button--primary">Filter</button>
                        @if (request()->has('severity') || request()->has('search'))
                            <a href="{{ route('admin.hack') }}" class="button button--ghost">Reset</a>
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
                        Percobaan Terdeteksi
                        <span class="badge badge--danger ms-2">{{ $stats['newCount'] }} baru</span>
                    </h2>
                    <p class="card__subtitle">
                        Percobaan yang sama dari IP yang sama dalam 10 menit otomatis digabung (lihat kolom jumlah).
                    </p>
                </div>
            </div>
            <div class="card__body">
                @if ($attempts->isEmpty())
                    <div class="empty-state empty-state--sm">
                        <p class="empty-state__title">Aman 🛡️</p>
                        <p class="empty-state__text">Belum ada percobaan mencurigakan yang terdeteksi.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table--hover table--sm">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Severitas</th>
                                    <th>IP address</th>
                                    <th>Target</th>
                                    <th>Alasan</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($attempts as $attempt)
                                    @php
                                        $sevClass = match ($attempt->severity) {
                                            'low' => 'badge--soft',
                                            'medium' => 'badge--warning',
                                            'high' => 'badge--danger',
                                            'critical' => 'badge--danger',
                                            default => 'badge--soft',
                                        };
                                    @endphp
                                    <tr style="{{ $attempt->is_new ? 'background:var(--color-accent-soft, rgba(59,130,246,.06))' : '' }}">
                                        <td class="text-muted-foreground text-xs whitespace-nowrap">{{ $attempt->created_at?->format('d M Y H:i') }}</td>
                                        <td>
                                            <span class="badge {{ $sevClass }}">
                                                {{ ucfirst($attempt->severity) }}
                                            </span>
                                            @if ($attempt->severity === 'critical')
                                                <span class="badge badge--danger" style="margin-left:2px">🚨</span>
                                            @endif
                                        </td>
                                        <td>
                                            <code>{{ $attempt->ip_address }}</code>
                                            @if ($attempt->count > 1)
                                                <span class="badge badge--soft ms-1">{{ $attempt->count }}x</span>
                                            @endif
                                        </td>
                                        <td>
                                            <code class="text-xs">{{ $attempt->method }} {{ $attempt->path }}</code>
                                            @if ($attempt->query_string)
                                                <div class="text-xs text-muted-foreground"
                                                    style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                                                    title="{{ $attempt->query_string }}">
                                                    {{ \Illuminate\Support\Str::limit($attempt->query_string, 70) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $attempt->reason }}
                                            @if ($attempt->is_new)
                                                <span class="badge badge--danger ms-1">baru</span>
                                            @endif
                                            <details style="margin-top:4px">
                                                <summary class="text-xs text-muted-foreground cursor-pointer" style="cursor:pointer">detail payload</summary>
                                                @if ($attempt->payload)
                                                    <code class="text-xs" style="display:block;margin-top:4px;word-break:break-all">{{ \Illuminate\Support\Str::limit($attempt->payload, 300) }}</code>
                                                @endif
                                                @if ($attempt->user_agent)
                                                    <p class="text-xs text-muted-foreground mt-1" style="word-break:break-all">UA: {{ \Illuminate\Support\Str::limit($attempt->user_agent, 120) }}</p>
                                                @endif
                                            </details>
                                        </td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('admin.hack.destroy', $attempt) }}"
                                                data-confirm="Hapus jejak ini?"
                                                data-confirm-ok="ya, hapus">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="button button--danger button--sm button--icon-only"
                                                    aria-label="Hapus jejak">
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
                    <div class="mt-4">{{ $attempts->links('vendor.pagination.stisla') }}</div>
                @endif
            </div>
        </div>
    </section>
@endsection
