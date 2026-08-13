{{-- ─── HALAMAN BROWSE / FEED PESAN ─── --}}
{{-- Ini halaman buat liat semua pesan yang udah dikirim, lengkap dengan search & filter --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Browse - SkanidaSong SMK</title>
    <link rel="icon" type="image/png" sizes="128x128" href="/favicon.png">
    {{-- Font dimuat lokal (same-origin) supaya html-to-image bisa meng-embed-nya
       ke gambar PNG "save as png". CDN fonts.bunny.net cross-origin gagal dibaca
       (SecurityError/CORS) → font fallback → tulisan tumpuk di gambar. --}}
    @include('partials.fonts')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#FFFFFF] text-gray-950 font-sans min-h-screen flex flex-col">
    {{-- ─── HEADER — SAMA KAYAK WELCOME, TAPI LINK NYA TERBALIK ─── --}}
    {{-- Bedanya: di sini "browse" jadi tombol hitam (aktif), "tell your story" jadi link biasa --}}
    <header class="border-b border-[#E9E9E9]">
        <div class="max-w-7xl mx-auto px-5 sm:px-8 lg:px-20 flex items-center justify-between h-14">
            <a href="{{ url('/') }}" class="font-reenie text-[28px] sm:text-[36px] leading-[100%] text-[#171717]">SkanidaSong</a>
            <nav class="flex items-center gap-4 sm:gap-6">
                <a href="{{ route('messages.index') }}"
                    class="text-sm font-semibold text-white bg-[#171717] px-4 py-2 rounded-xl hover:bg-gray-800 transition-colors">browse</a>
                <a href="{{ route('story.create') }}"
                    class="text-sm text-gray-500 hover:text-gray-950 transition-colors">tell your story</a>
            </nav>
        </div>
    </header>

    {{-- ─── KONTEN UTAMA ─── --}}
    <main class="flex-1 max-w-7xl mx-auto w-full px-5 sm:px-8 lg:px-20 py-8">
        {{-- Judul halaman --}}
        <div class="text-center mb-8">
            <h1 class="font-reenie text-[48px] sm:text-[60px] leading-[100%] text-[#171717]">browse</h1>
            <p class="text-gray-500 text-sm sm:text-base mt-3">find a story</p>
        </div>

        {{-- Flash message sukses (kalo abis kirim pesan) --}}
        @if (session('success'))
            <div class="mb-6 p-4 rounded-xl bg-blue-600/10 border border-blue-900/20 text-blue-900 text-sm text-center">
                {{ session('success') }}
            </div>
        @endif

        {{-- ─── SEARCH & FILTER BAR ─── --}}
        {{-- Method GET biar hasil filter bisa di-bookmark atau di-share --}}
        <div class="max-w-xl mx-auto mb-10">
            <form method="GET" action="{{ route('messages.index') }}" class="space-y-4">
                <div class="flex flex-col sm:flex-row gap-3">
                    {{-- Input search berdasarkan nama --}}
                    <div class="flex-1">
                        {{-- value="{{ $search }}" = biar inputnya gak ilang pas disubmit --}}
                        <input type="text" name="search" id="search" value="{{ $search }}"
                            placeholder="search by name..."
                            class="w-full px-4 py-3 rounded-xl border border-[#D9D9D9] text-gray-950 placeholder:text-gray-400 focus:border-gray-950 focus:ring-1 focus:ring-gray-200 outline-none transition-colors">
                    </div>
                    {{-- Dropdown filter kelas (custom, tanpa arrow browser) — ngambil opsi dari $kelasList --}}
                    <div class="w-full sm:w-fit">
                        <input type="hidden" name="kelas" id="kelas" value="{{ $selectedKelas }}">
                        <div id="kelas-dd" class="relative">
                            <button type="button" id="kelas-btn"
                                class="w-full px-4 py-3 rounded-xl border border-[#D9D9D9] text-left @if($selectedKelas) text-gray-950 @else text-gray-400 @endif hover:border-gray-950 focus:border-gray-950 focus:ring-1 focus:ring-gray-200 outline-none transition-colors cursor-pointer bg-white whitespace-nowrap">
                                {{ $selectedKelas ?: 'Semua' }}
                            </button>
                            <ul id="kelas-list"
                                class="hidden absolute z-20 mt-1 w-full min-w-[180px] max-h-64 overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg">
                                <li data-value=""
                                    class="px-4 py-2.5 text-gray-950 hover:bg-gray-100 cursor-pointer list-none text-sm whitespace-nowrap">Semua</li>
                                @foreach ($kelasList as $k)
                                    <li data-value="{{ $k }}"
                                        class="px-4 py-2.5 text-gray-950 hover:bg-gray-100 cursor-pointer list-none text-sm whitespace-nowrap">{{ $k }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    {{-- Tombol search + reset --}}
                    <div class="flex gap-2">
                        <button type="submit"
                            class="px-5 py-3 rounded-xl bg-[#171717] hover:bg-gray-800 text-white text-sm font-medium transition-colors">
                            search
                        </button>
                        {{-- Tombol reset — tinggal arahin ke /messages tanpa parameter, filter ilang semua --}}
                        <a href="{{ route('messages.index') }}"
                            class="px-5 py-3 rounded-xl border border-[#D9D9D9] text-gray-500 hover:text-gray-950 hover:border-[#171717] transition-colors text-sm text-center">
                            reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- ─── DAFTAR PESAN ─── --}}
        {{-- Kalo hasil query kosong — $messages->isEmpty() --}}
        @if ($messages->isEmpty())
            <div class="text-center py-16">
                <p class="text-gray-500">no stories found.</p>
                <p class="text-gray-400 text-sm mt-2">be the first one to tell a story!</p>
            </div>
        @else
            {{-- Grid responsive: 1 kolom (HP), 2 kolom (tablet), 3 kolom (desktop) --}}
            {{-- Infinite scroll: grid jadi target append kartu berikutnya (data-infinite-grid) --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-4 lg:gap-6" data-infinite-grid
                data-next-url="{{ $nextUrl }}"
                data-has-more="{{ $nextUrl ? '1' : '0' }}">
                @include('messages.partials.cards', ['messages' => $messages, 'myReactions' => $myReactions])
            </div>

            {{-- ─── INFINITE SCROLL STATUS ─── --}}
            {{-- Sentinel dipantau IntersectionObserver; spinner/status dirender di sini biar
                class Tailwind ikut ter-generate (Tailwind hanya memindai *.blade.php). --}}
            <div data-infinite-sentinel class="h-px"></div>
            <div class="mt-10 text-center" data-infinite-status>
                <div data-infinite-loading class="hidden flex items-center justify-center gap-2 text-gray-500 text-sm">
                    <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                    </svg>
                    <span>memuat cerita lainnya...</span>
                </div>
                <div data-infinite-error class="hidden">
                    <p class="text-gray-500 text-sm mb-3">gagal memuat cerita.</p>
                    <button type="button" data-infinite-retry
                        class="px-4 py-2 rounded-xl border border-[#D9D9D9] text-gray-500 hover:text-gray-950 hover:border-[#171717] transition-colors text-sm cursor-pointer">
                        coba lagi
                    </button>
                </div>
                <div data-infinite-end class="hidden text-gray-400 text-sm">
                    kamu sudah membaca semua cerita 🎉
                </div>
            </div>
        @endif
    </main>

    {{-- ─── FOOTER ─── --}}
    <footer class="border-t border-[#E9E9E9] py-6 mt-auto">
        <p class="text-center text-gray-500 text-xs">Artifact studios &copy; {{ date('Y') }} &mdash; SkanidaSong</p>
    </footer>

    {{-- ─── JS: DROPDOWN KELAS CUSTOM (auto submit filter) ─── --}}
    <script>
        (function () {
            var dd = document.getElementById('kelas-dd');
            if (!dd) return;
            var btn = document.getElementById('kelas-btn');
            var list = document.getElementById('kelas-list');
            var input = document.getElementById('kelas');
            var form = btn.form;

            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                list.classList.toggle('hidden');
            });

            list.querySelectorAll('[data-value]').forEach(function (li) {
                li.addEventListener('click', function () {
                    input.value = li.getAttribute('data-value');
                    if (form) form.submit();
                });
            });

            document.addEventListener('click', function () {
                list.classList.add('hidden');
            });
        })();
    </script>
</body>

</html>
