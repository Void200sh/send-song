{{-- ─── DOKUMEN HTML ─── --}}
{{-- Ini adalah halaman utama (landing page) SkanidaSong — isinya stats, marquee, dan form kirim pesan --}}
<!DOCTYPE html>
{{-- app()->getLocale() ngambil bahasa dari config Laravel (default: 'id' atau 'en') --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    {{-- Viewport biar responsive di HP --}}
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SkanidaSong - SMK</title>
    <link rel="icon" type="image/png" sizes="128x128" href="/favicon.png">
    {{-- Preconnect ke fonts.bunny.net biar loading font lebih cepet --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    {{-- Load 2 font: Reenie Beanie (font dekoratif buat judul) sama Plus Jakarta Sans (font utama) --}}
    <link href="https://fonts.bunny.net/css?family=reenie-beanie:400|plus-jakarta-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

{{-- ─── BODY ─── --}}
{{-- bg-[#FFFFFF] = putih, font-sans = Plus Jakarta Sans, min-h-screen flex flex-col = biar footer nempel di bawah --}}
<body class="bg-[#FFFFFF] text-gray-950 font-sans min-h-screen flex flex-col">
    {{-- ─── HEADER / NAVBAR ─── --}}
    {{-- border-b = garis bawah tipis --}}
    <header class="border-b border-[#E9E9E9]">
        <div class="max-w-7xl mx-auto px-5 sm:px-8 lg:px-20 flex items-center justify-between h-14">
            {{-- Logo — pake font Reenie Beanie yang besar --}}
            <a href="{{ url('/') }}" class="font-reenie text-[28px] sm:text-[36px] leading-[100%] text-[#171717]">SkanidaSong</a>
            <nav class="flex items-center gap-4 sm:gap-6">
                {{-- Link ke halaman browse — di landing page, link ini tampil biasa (gak aktif) --}}
                <a href="{{ route('messages.index') }}"
                    class="text-sm text-gray-500 hover:text-gray-950 transition-colors">browse</a>
                {{-- Link ke halaman kirim story — tampil sebagai tombol hitam "tell your story" --}}
                <a href="{{ route('story.create') }}"
                    class="text-sm font-semibold text-white bg-[#171717] px-4 py-2 rounded-xl hover:bg-gray-800 transition-colors">tell your story</a>
            </nav>
        </div>
    </header>

    {{-- ─── KONTEN UTAMA ─── --}}
    <main class="flex-1 w-full">
        {{-- ─── SECTION: STATS CARDS ─── --}}
        <div class="max-w-7xl mx-auto px-5 sm:px-8 lg:px-20 pt-8 sm:pt-[140px] pb-8">
            {{-- Judul halaman + tagline --}}
            <div class="text-center mb-10">
                <h1 class="font-reenie text-[48px] sm:text-[60px] leading-[100%] text-[#171717]">SkanidaSong</h1>
                <p class="text-gray-500 text-sm sm:text-base mt-3">Kata-kata yang tak pernah terkatakan, tersampaikan lewat sebuah lagu</p>
            </div>

            {{-- Grid 3 kolom stats card (di HP jadi 1 kolom) --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 max-w-3xl mx-auto mb-12">
                {{-- Card 1: Total pesan --}}
                <div class="border border-[#E9E9E9] rounded-xl p-5 text-center bg-white">
                    {{-- $totalMessages dari controller — jumlah semua record di tabel messages --}}
                    <p class="font-reenie text-[36px] leading-[100%] text-[#171717]">{{ $totalMessages }}</p>
                    <p class="text-sm text-gray-500 mt-2">stories told</p>
                </div>
                {{-- Card 2: Total kelas yang pernah dikirimin pesan --}}
                <div class="border border-[#E9E9E9] rounded-xl p-5 text-center bg-white">
                    {{-- $totalKelas dari controller — jumlah kelas UNIK --}}
                    <p class="font-reenie text-[36px] leading-[100%] text-[#171717]">{{ $totalKelas }}</p>
                    <p class="text-sm text-gray-500 mt-2">classes reached</p>
                </div>
                {{-- Card 3: Waktu pesan terbaru --}}
                <div class="border border-[#E9E9E9] rounded-xl p-5 text-center bg-white">
                    {{-- diffForHumans() ngubah tanggal jadi "3 hours ago", "2 days ago", dll --}}
                    {{-- Kalo belum ada pesan sama sekali (?), tampilin strip (-) --}}
                    <p class="font-reenie text-[36px] leading-[100%] text-[#171717]">
                        {{ $latestMessage ? $latestMessage->created_at->diffForHumans() : '-' }}
                    </p>
                    <p class="text-sm text-gray-500 mt-2">latest story</p>
                </div>
            </div>

            {{-- Tombol CTA — arah ke halaman kirim story (terpisah dari index) --}}
            <div class="text-center mb-12 flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="{{ route('story.create') }}"
                    class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-[#171717] hover:bg-gray-800 text-white text-sm font-semibold transition-colors">
                    tell your story
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
                <a href="{{ route('messages.index') }}"
                    class="inline-flex items-center gap-2 px-6 py-3 rounded-xl border border-[#D9D9D9] hover:border-[#171717] text-gray-700 text-sm font-semibold transition-colors">
                    lihat semua
                </a>
            </div>
        </div>

        {{-- ─── MARQUEE KARTU 1: KARTU PESAN BERJALAN (KANAN KE KIRI) ─── --}}
        {{-- Kartu bergulir ala sendthesong.xyz: cover lagu + judul + isi pesan + badge "to: [nama]" --}}
        {{-- Konten di-duplikat 2× biar infinite loop mulus (translate -50%). Pause saat hover. --}}
        @if ($marqueeMessages->isNotEmpty())
            <div class="group border-y border-[#E9E9E9] overflow-hidden py-4 mb-8">
                <div class="flex animate-marquee group-hover:[animation-play-state:paused]">
                    {{-- Div 1: konten asli --}}
                    <div class="flex gap-4 shrink-0">
                        @foreach ($marqueeMessages->take(10) as $msg)
                            @include('partials.marquee-card', ['msg' => $msg])
                        @endforeach
                    </div>
                    {{-- Div 2: DUPLIKAT (biar loop mulus, gak ada jeda kosong) — disembunyikan dari screen reader --}}
                    <div class="flex gap-4 shrink-0" aria-hidden="true">
                        @foreach ($marqueeMessages->take(10) as $msg)
                            @include('partials.marquee-card', ['msg' => $msg])
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- ─── MARQUEE KARTU 2: KARTU PESAN BERJALAN (KIRI KE KANAN — REVERSE) ─── --}}
        {{-- Baris kedua: setengah pesan sisanya, arahnya kebalikan (reverse). Pause saat hover. --}}
        @if ($marqueeMessages->count() > 10)
            <div class="group border-b border-[#E9E9E9] overflow-hidden py-4 mb-8">
                <div class="flex animate-marquee-reverse group-hover:[animation-play-state:paused]">
                    <div class="flex gap-4 shrink-0">
                        @foreach ($marqueeMessages->skip(10)->take(10) as $msg)
                            @include('partials.marquee-card', ['msg' => $msg])
                        @endforeach
                    </div>
                    <div class="flex gap-4 shrink-0" aria-hidden="true">
                        @foreach ($marqueeMessages->skip(10)->take(10) as $msg)
                            @include('partials.marquee-card', ['msg' => $msg])
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        </main>

    {{-- ─── FOOTER ─── --}}
    <footer class="border-t border-[#E9E9E9] py-6">
        {{-- date('Y') nampilin tahun sekarang otomatis --}}
        <p class="text-center text-gray-500 text-xs">Artifac studios &copy; {{ date('Y') }} &mdash; SkanidaSong</p>
    </footer>
</body>

</html>
