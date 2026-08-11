{{-- ─── HALAMAN AUDIT SECURITY — PANTAU AKTIVITAS ADMIN, LOGIN & IP TER-BAN ─── --}}
@extends('admin.layouts.app')

@section('title', 'Audit Security')

@section('content')
    <section class="page">
        <div class="page__header">
            <div class="page__headline">
                <h1 class="page__title font-reenie">Audit Security 🛡️</h1>
                <p class="page__description">
                    Jejak lengkap aktivitas di panel admin: siapa melakukan apa, riwayat login (termasuk yang mencurigakan),
                    dan daftar IP yang diblokir.
                </p>
            </div>
            <div class="page__action" style="display:flex;gap:8px;flex-wrap:wrap">
                <a href="{{ route('admin.export.audit') }}" class="button button--outline button--sm">
                    ⬇️ Export Audit
                </a>
                <a href="{{ route('admin.export.logins') }}" class="button button--outline button--sm">
                    ⬇️ Export Login
                </a>
                <a href="{{ route('admin.export.hack') }}" class="button button--outline button--sm">
                    ⬇️ Export Hack
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
            <div class="col-span-12 sm:col-span-6 xl:col-span-2">
                <div class="card card--stat">
                    <div class="card__body">
                        <div class="stat">
                            <p class="stat__label text-eyebrow">Aktivitas Admin</p>
                            <p class="stat__value font-reenie">{{ number_format($stats['totalActions']) }}</p>
                            <p class="stat__meta text-muted-foreground text-sm">total aksi tercatat</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-span-12 sm:col-span-6 xl:col-span-2">
                <div class="card card--stat">
                    <div class="card__body">
                        <div class="stat">
                            <p class="stat__label text-eyebrow">Aktivitas Hari Ini</p>
                            <p class="stat__value font-reenie">{{ number_format($stats['todayActions']) }}</p>
                            <p class="stat__meta text-muted-foreground text-sm">{{ \Carbon\Carbon::today()->format('d M') }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-span-12 sm:col-span-6 xl:col-span-2">
                <div class="card card--stat">
                    <div class="card__body">
                        <div class="stat">
                            <p class="stat__label text-eyebrow">Login Total</p>
                            <p class="stat__value font-reenie">{{ number_format($stats['totalLogins']) }}</p>
                            <p class="stat__meta text-muted-foreground text-sm">sukses + gagal</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-span-12 sm:col-span-6 xl:col-span-2">
                <div class="card card--stat">
                    <div class="card__body">
                        <div class="stat">
                            <p class="stat__label text-eyebrow">Login Gagal</p>
                            <p class="stat__value font-reenie" style="color:{{ $stats['failedLogins'] > 0 ? '#dc2626' : 'inherit' }}">{{ number_format($stats['failedLogins']) }}</p>
                            <p class="stat__meta text-muted-foreground text-sm">percobaan gagal</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-span-12 sm:col-span-6 xl:col-span-2">
                <div class="card card--stat">
                    <div class="card__body">
                        <div class="stat">
                            <p class="stat__label text-eyebrow">Login Mencurigakan</p>
                            <p class="stat__value font-reenie" style="color:{{ $stats['suspiciousLogins'] > 0 ? '#dc2626' : 'inherit' }}">{{ number_format($stats['suspiciousLogins']) }}</p>
                            <p class="stat__meta text-muted-foreground text-sm">IP baru per user</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-span-12 sm:col-span-6 xl:col-span-2">
                <div class="card card--stat">
                    <div class="card__body">
                        <div class="stat">
                            <p class="stat__label text-eyebrow">IP Ter-ban</p>
                            <p class="stat__value font-reenie">{{ number_format($stats['bannedIps']) }}</p>
                            <p class="stat__meta text-muted-foreground text-sm">diblokir kirim pesan</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── NAV TAB ─── --}}
        <div class="card">
            <div class="card__body" style="padding-bottom:0">
                <div style="display:flex;gap:6px;flex-wrap:wrap;border-bottom:1px solid var(--color-border);margin-bottom:16px">
                    @php
                        $tab = request('tab', 'activity');
                        $tabs = [
                            'activity' => ['Aktivitas Admin', 'Aktivitas Admin'],
                            'logins' => ['Riwayat Login', 'Riwayat Login'],
                            'bans' => ['IP Ter-ban', 'IP Ter-ban'],
                        ];
                    @endphp
                    @foreach ($tabs as $key => [$label, $title])
                        <a href="{{ route('admin.audit', ['tab' => $key]) }}"
                            style="padding:10px 16px;font-size:13.5px;font-weight:600;border-radius:10px 10px 0 0;text-decoration:none;
                                {{ $tab === $key ? 'background:var(--color-accent,#171717);color:#fff' : 'color:var(--color-muted-foreground,#6b7280)' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>

                @if ($tab === 'activity')
                    {{-- ─── TAB 1: AKTIVITAS ADMIN ─── --}}
                    <form method="GET" action="{{ route('admin.audit') }}" class="flex flex-wrap gap-3 items-end mb-4">
                        <input type="hidden" name="tab" value="activity">
                        <div class="input-group input-group--search" style="flex: 1 1 16rem; min-width: 0;">
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="cari admin / aksi / IP..." class="input">
                        </div>
                        <button type="submit" class="button button--primary">Cari</button>
                        @if (request()->has('search') && $tab === 'activity')
                            <a href="{{ route('admin.audit', ['tab' => 'activity']) }}" class="button button--ghost">Reset</a>
                        @endif
                    </form>

                    <div class="table-responsive">
                        <table class="table table--hover table--sm">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Admin</th>
                                    <th>Aksi</th>
                                    <th>Detail</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($auditLogs as $log)
                                    <tr>
                                        <td class="text-muted-foreground text-xs whitespace-nowrap">{{ $log->created_at?->format('d M Y H:i') }}</td>
                                        <td class="font-medium">{{ $log->user_name ?: 'Sistem' }}</td>
                                        <td><code class="text-xs">{{ $log->action }}</code></td>
                                        <td class="text-sm" style="max-width:320px">
                                            @if ($log->details)
                                                <span class="text-muted-foreground text-xs">
                                                    {{ collect($log->details)->map(fn ($v, $k) => $k . '=' . (is_scalar($v) ? $v : json_encode($v)))->implode(', ') }}
                                                </span>
                                            @else
                                                <span class="text-muted-foreground text-xs">—</span>
                                            @endif
                                        </td>
                                        <td><code class="text-xs">{{ $log->ip_address ?: '—' }}</code></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted-foreground py-6">Belum ada aktivitas admin yang tercatat.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $auditLogs->appends(['tab' => 'activity'])->links('vendor.pagination.stisla') }}</div>

                @elseif ($tab === 'logins')
                    {{-- ─── TAB 2: RIWAYAT LOGIN ─── --}}
                    <div class="flex flex-wrap gap-3 items-end mb-4">
                        <form method="GET" action="{{ route('admin.audit') }}" class="flex flex-wrap gap-3 items-end">
                            <input type="hidden" name="tab" value="logins">
                            <div>
                                <select name="login_status" class="select">
                                    <option value="">semua status</option>
                                    <option value="success" {{ request('login_status') === 'success' ? 'selected' : '' }}>sukses</option>
                                    <option value="failed" {{ request('login_status') === 'failed' ? 'selected' : '' }}>gagal</option>
                                </select>
                            </div>
                            <button type="submit" class="button button--primary">Filter</button>
                        </form>
                        <form method="POST" action="{{ route('admin.audit.logins-read') }}">
                            @csrf
                            <button type="submit" class="button button--outline button--sm">Tandai dibaca</button>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table--hover table--sm">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Mencurigakan</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($loginLogs as $log)
                                    <tr style="{{ $log->is_new ? 'background:var(--color-accent-soft, rgba(59,130,246,.06))' : '' }}">
                                        <td class="text-muted-foreground text-xs whitespace-nowrap">{{ $log->created_at?->format('d M Y H:i') }}</td>
                                        <td class="font-medium">{{ $log->email }}</td>
                                        <td>
                                            @if ($log->status === 'success')
                                                <span class="badge badge--soft">sukses</span>
                                            @else
                                                <span class="badge badge--danger">gagal</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($log->is_suspicious)
                                                <span class="badge badge--danger">🚨 mencurigakan</span>
                                            @else
                                                <span class="text-muted-foreground text-xs">tidak</span>
                                            @endif
                                        </td>
                                        <td><code class="text-xs">{{ $log->ip_address ?: '—' }}</code></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted-foreground py-6">Belum ada riwayat login.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $loginLogs->appends(['tab' => 'logins'])->links('vendor.pagination.stisla') }}</div>

                @else
                    {{-- ─── TAB 3: IP TER-BAN ─── --}}
                    <p class="text-sm text-muted-foreground mb-4">
                        Daftar IP yang diblokir dari mengirim pesan. Ban otomatis berasal dari deteksi spam;
                        ban manual dari laporan atau aksi admin. Klik tombol hapus untuk membuka blokir (unban).
                    </p>
                    <div class="table-responsive">
                        <table class="table table--hover table--sm">
                            <thead>
                                <tr>
                                    <th>IP Address</th>
                                    <th>Nama</th>
                                    <th>Sumber</th>
                                    <th>Diblokir Oleh</th>
                                    <th>Alasan</th>
                                    <th>Waktu Ban</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($bans as $ban)
                                    <tr>
                                        <td><code>{{ $ban->ip_address }}</code></td>
                                        <td>
                                            @if ($ban->sender_key === '*')
                                                <span class="badge badge--danger">Semua pengirim</span>
                                            @else
                                                {{ $ban->sender_name ?: ($ban->sender_key === 'anonymous' ? 'Anonim' : $ban->sender_key) }}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($ban->ban_source === 'manual')
                                                <span class="badge badge--danger">manual</span>
                                            @else
                                                <span class="badge badge--soft">otomatis</span>
                                            @endif
                                        </td>
                                        <td>{{ $ban->bannedBy?->name ?: '—' }}</td>
                                        <td class="text-sm" style="max-width:240px">{{ $ban->reason }}</td>
                                        <td class="text-muted-foreground text-xs whitespace-nowrap">{{ $ban->banned_at?->format('d M Y H:i') }}</td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('admin.audit.unban', $ban) }}"
                                                data-confirm="Unban IP {{ $ban->ip_address }}? IP ini bisa mengirim pesan lagi."
                                                data-confirm-ok="ya, unban"
                                                data-confirm-title="Unban IP">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="button button--danger button--sm button--icon-only"
                                                    aria-label="Unban IP" title="Unban IP {{ $ban->ip_address }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                                                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <circle cx="12" cy="12" r="9" />
                                                        <path d="M7.5 12h9" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted-foreground py-6">Tidak ada IP yang di-ban. 🎉</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $bans->appends(['tab' => 'bans'])->links('vendor.pagination.stisla') }}</div>
                @endif
            </div>
        </div>
    </section>
@endsection
