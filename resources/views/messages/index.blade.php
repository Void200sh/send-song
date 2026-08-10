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
    {{-- Preconnect ke fonts.bunny.net biar loading font lebih cepet --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    {{-- Load 2 font: Reenie Beanie (font dekoratif buat judul) sama Plus Jakarta Sans (font utama) --}}
    <link href="https://fonts.bunny.net/css?family=reenie-beanie:400|plus-jakarta-sans:400,500,600,700" rel="stylesheet" />
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
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-4 lg:gap-6">
                @foreach ($messages as $msg)
                    {{-- ─── SATU CARD PESAN — semuanya link ke halaman detail (kecuali elemen interaktif) ─── --}}
                    {{-- Tema kartu: theme-{key} dari kolom theme (null = polos/classic) --}}
                    <a href="{{ route('messages.show', $msg) }}" data-msg-card data-detail-url="{{ route('messages.show', $msg) }}"
                        class="block relative overflow-hidden border border-[#E9E9E9] rounded-xl p-5 transition-colors hover:border-[#171717] hover:shadow-sm theme-{{ $msg->theme ?: 'classic' }} cursor-pointer">
                        @include('partials.theme-decor', ['theme' => $msg->theme])
                        {{-- Badge pin (dipasang admin) — tampil di pojok kanan atas, di samping tombol share --}}
                        @if ($msg->is_pinned)
                            <span class="absolute top-3 right-12 flex items-center gap-1 text-[11px] font-semibold text-white bg-[#171717]/90 rounded-full px-2 py-1 shadow-sm">📌 pinned</span>
                        @endif
                        {{-- Tombol share — bagikan LINK pesan ini (tanpa gambar). Klik aman: tidak membuka halaman detail. --}}
                        <button type="button" data-share-card
                            data-share-url="{{ route('messages.show', $msg) }}"
                            data-share-recipient="{{ $msg->recipient_name }}"
                            title="share pesan ini"
                            aria-label="share pesan ini"
                            class="absolute top-3 right-3 z-10 w-8 h-8 flex items-center justify-center rounded-full bg-white/85 border border-[#E9E9E9] text-gray-500 hover:text-gray-950 hover:border-[#171717] transition-colors cursor-pointer shadow-sm backdrop-blur">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8M16 6l-4-4-4 4M12 2v13"/></svg>
                        </button>
                        {{-- Nama pengirim & penerima (font Reenie Beanie identik) --}}
                        <div class="mb-2">
                            <p class="font-reenie text-[28px] sm:text-[32px] leading-[100%] text-[#171717]">from: {!! \App\Support\EmojiText::small($msg->sender_name ?: 'anonymous') !!}</p>
                            <p class="font-reenie text-[28px] sm:text-[32px] leading-[100%] text-[#171717]">to: {!! \App\Support\EmojiText::small($msg->recipient_name) !!}</p>
                        </div>
                        {{-- Kelas + waktu + views (relatif, ex: "XI PPLG 1 • 2 hours ago • 👁 12") --}}
                        <div class="text-xs text-gray-500 mb-3 flex items-center gap-2">
                            <span>{{ $msg->kelas }} &bull; {{ $msg->created_at->diffForHumans() }}</span>
                            <span class="inline-flex items-center gap-0.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                <span>{{ $msg->views }}</span>
                            </span>
                        </div>
                        {{-- Isi pesan (font Reenie Beanie, sama seperti from/to) --}}
                        <p class="font-reenie text-[20px] leading-[100%] text-[#171717] mb-4">{!! \App\Support\EmojiText::small(\Illuminate\Support\Str::limit($msg->message, 80)) !!}</p>
                        {{-- Blok lagu: judul & artis SELALU tampil kalau ada lagu. Player YouTube kalau ada id-nya, link Spotify kalau ada. --}}
                        @if ($msg->song_title || $msg->youtube_video_id || $msg->spotify_track_id)
                            @if ($msg->youtube_video_id)
                            <div data-player-card data-video-id="{{ $msg->youtube_video_id }}"
                                data-clip-start="{{ $msg->clip_start_seconds }}"
                                data-clip-end="{{ $msg->clip_end_seconds }}"
                                class="mt-3 rounded-xl border border-gray-200 bg-gray-50 p-3 transition-shadow">
                                <div class="flex items-center gap-3">
                                    @if ($msg->cover_url)
                                        <img src="{{ $msg->cover_url }}" class="w-11 h-11 rounded-lg object-cover" alt="cover">
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-gray-900 truncate">{{ $msg->song_title }}</p>
                                        <p class="text-xs text-gray-500 truncate">{{ $msg->song_artist }}</p>
                                    </div>
                                    {{-- Tombol Play/Pause — ngarah ke playVideo()/pauseVideo() di player.js --}}
                                    <button type="button" data-play
                                        class="w-10 h-10 shrink-0 rounded-full bg-[#171717] hover:bg-gray-800 text-white flex items-center justify-center">
                                        <svg data-icon-play class="w-4 h-4 ml-0.5" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.8a1 1 0 0 1 1.5-.9l10 6a1 1 0 0 1 0 1.7l-10 6a1 1 0 0 1-1.5-.9V2.8z"/></svg>
                                        <svg data-icon-pause class="hidden w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M5 3h3v14H5V3zm7 0h3v14h-3V3z"/></svg>
                                    </button>
                                </div>
                                {{-- Seekbar + waktu — dihubungkan ke seekTo() di player.js --}}
                                <div class="flex items-center gap-2 mt-3">
                                    <span data-current class="text-[11px] text-gray-500 w-8">0:00</span>
                                    <div class="relative flex-1 h-1.5 bg-gray-200 rounded-full cursor-pointer">
                                        <div data-progress class="absolute left-0 top-0 h-full bg-gray-900 rounded-full" style="width:0%"></div>
                                        <input data-seekbar type="range" min="0" max="1000" value="0"
                                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                    </div>
                                    <span data-duration class="text-[11px] text-gray-500 w-8 text-right">{{ $msg->display_duration }}</span>
                                </div>
                                {{-- Fallback kalo video YouTube error/dihapus — button biar gak nested <a> di dalam <a> --}}
                                <button type="button" data-fallback data-url="https://open.spotify.com/track/{{ $msg->spotify_track_id }}"
                                    class="hidden mt-2 text-xs text-green-600 hover:underline bg-transparent border-0 p-0 m-0 text-left cursor-pointer">
                                    video tidak tersedia — buka di Spotify
                                </button>
                            </div>
                            @else
                                {{-- Lagu tanpa YouTube ID: judul & artis tetap tampil + tombol Spotify kalau ada id-nya --}}
                                <div class="mt-3 rounded-xl border border-gray-200 bg-gray-50 p-3">
                                    <div class="flex items-center gap-3">
                                        @if ($msg->cover_url)
                                            <img src="{{ $msg->cover_url }}" class="w-11 h-11 rounded-lg object-cover" alt="cover">
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $msg->song_title }}</p>
                                            @if ($msg->song_artist)
                                                <p class="text-xs text-gray-500 truncate">{{ $msg->song_artist }}</p>
                                            @endif
                                        </div>
                                        @if ($msg->spotify_track_id)
                                            <button type="button" data-open-url="https://open.spotify.com/track/{{ $msg->spotify_track_id }}"
                                                class="shrink-0 inline-flex items-center gap-1.5 text-xs text-green-600 hover:underline cursor-pointer bg-transparent border-0 p-0 m-0 text-left"
                                                title="Buka lagu di Spotify">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16zm3.6 11.6a.5.5 0 0 1-.7.2c-1.9-1.2-4.4-1.5-7.2-.8a.5.5 0 0 1-.2-1c3-.7 5.8-.4 7.9 1a.5.5 0 0 1 .2.6zm1-2.2a.6.6 0 0 1-.8.3c-2.2-1.4-5.6-1.7-8.2-1a.6.6 0 0 1-.4-1.2c3-.8 6.8-.5 9.2 1a.6.6 0 0 1 .2.9zm.1-2.3c-2.7-1.6-7-1.7-9.6-1a.7.7 0 0 1-.4-1.4c3-1 7.5-.8 10.5 1a.7.7 0 0 1-.5 1.3z"/></svg>
                                                buka
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endif

                        {{-- Reaksi emoji ala WhatsApp — tombol di dalam <a> aman: klik button tidak navigasi --}}
                        @include('partials.reactions', ['msg' => $msg, 'mine' => $myReactions->get($msg->id, collect())->all()])
                    </a>
                @endforeach
            </div>

            {{-- ─── PAGINATION ─── --}}
            <div class="mt-8">
                {{-- appends(request()->query()) = biar parameter ?search=... &kelas=... gak ilang pas pindah halaman --}}
                {{-- links() = nampilin tombol Previous/Next + nomor halaman --}}
                {{ $messages->appends(request()->query())->links() }}
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
