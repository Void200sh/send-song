{{-- ─── HALAMAN DETEKSI SPAM ─── --}}
@extends('admin.layouts.app')

@section('title', 'Notifikasi Spam')

@section('content')
    <section class="page">
        <div class="page__header">
            <div class="page__headline">
                <h1 class="page__title font-reenie">Notifikasi Spam</h1>
                <p class="page__description">Pesan yang terdeteksi bot dikelompokkan berdasarkan nama pengirim dan IP.</p>
            </div>
            <div class="page__action">
                <a href="{{ route('admin.dashboard') }}" class="button button--outline button--sm">Kembali</a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert--success">
                <div class="alert__title">Berhasil</div>
                <div class="alert__description">{{ session('success') }}</div>
            </div>
        @endif

        <div class="card">
            <div class="card__header">
                <div class="card__heading">
                    <h2 class="card__title">Pengirim Terdeteksi Spam</h2>
                    <p class="card__subtitle">Jumlah dihitung dari pesan baru yang ditandai bot.</p>
                </div>
            </div>
            <div class="card__body">
                @if ($offenders->isEmpty())
                    <div class="empty-state empty-state--sm">
                        <p class="empty-state__title">Tidak ada spam</p>
                        <p class="empty-state__text">Belum ada pesan yang memenuhi aturan deteksi spam.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table--hover table--sm">
                            <thead>
                                <tr>
                                    <th>Nama pengirim</th>
                                    <th>IP address</th>
                                    <th>Jumlah spam</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($offenders as $offender)
                                    <tr>
                                        <td>
                                            <span class="font-medium">{{ $offender->sender_name ?: ($offender->sender_key === 'anonymous' ? 'Anonim' : $offender->sender_key) }}</span>
                                        </td>
                                        <td><code>{{ $offender->ip_address }}</code></td>
                                        <td><span class="badge badge--danger">{{ $offender->spam_total }} spam</span></td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('admin.spam.destroy-group') }}"
                                                data-confirm="Hapus SEMUA pesan dari nama dan IP ini? Tindakan ini tidak bisa dibatalkan."
                                                data-confirm-ok="ya, hapus semua"
                                                data-confirm-title="Hapus semua pesan"
                                                class="inline-flex items-center gap-2">
                                                @csrf
                                                <input type="hidden" name="sender_key" value="{{ $offender->sender_key }}">
                                                <input type="hidden" name="ip_address" value="{{ $offender->ip_address }}">
                                                <button type="submit" class="button button--danger button--sm">Hapus semua messages</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $offenders->links('vendor.pagination.stisla') }}</div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card__header">
                <div class="card__heading">
                    <h2 class="card__title">Ban Otomatis</h2>
                    <p class="card__subtitle">Kombinasi nama + IP diblokir setelah mencapai 10 spam.</p>
                </div>
            </div>
            <div class="card__body">
                @if ($bans->isEmpty())
                    <p class="text-muted-foreground">Belum ada identitas yang diblokir.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table--sm">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>IP address</th>
                                    <th>Total spam</th>
                                    <th>Diblokir</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($bans as $ban)
                                    <tr>
                                        <td>{{ $ban->sender_name ?: ($ban->sender_key === 'anonymous' ? 'Anonim' : $ban->sender_key) }}</td>
                                        <td><code>{{ $ban->ip_address }}</code></td>
                                        <td><span class="badge badge--danger">{{ $ban->spam_count }}</span></td>
                                        <td class="text-muted-foreground text-xs">{{ $ban->banned_at?->format('d M Y H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $bans->links('vendor.pagination.stisla') }}</div>
                @endif
            </div>
        </div>
    </section>
@endsection
