{{-- ─── HALAMAN KIRIM STORY ─── --}}
{{-- Halaman ini cuma berisi form kirim story, terpisah dari halaman index --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Tell your story - SkanidaSong SMK</title>
    <link rel="icon" type="image/png" sizes="128x128" href="/favicon.png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=reenie-beanie:400|plus-jakarta-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#FFFFFF] text-gray-950 font-sans min-h-screen flex flex-col">
    {{-- ─── HEADER / NAVBAR ─── --}}
    <header class="border-b border-[#E9E9E9]">
        <div class="max-w-7xl mx-auto px-5 sm:px-8 lg:px-20 flex items-center justify-between h-14">
            <a href="{{ url('/') }}" class="font-reenie text-[28px] sm:text-[36px] leading-[100%] text-[#171717]">SkanidaSong</a>
            <nav class="flex items-center gap-4 sm:gap-6">
                <a href="{{ route('messages.index') }}"
                    class="text-sm text-gray-500 hover:text-gray-950 transition-colors">browse</a>
                {{-- Link halaman sendiri sebagai tombol hitam (aktif) --}}
                <a href="{{ route('story.create') }}"
                    class="text-sm font-semibold text-white bg-[#171717] px-4 py-2 rounded-xl hover:bg-gray-800 transition-colors">tell your story</a>
            </nav>
        </div>
    </header>

    {{-- ─── KONTEN UTAMA ─── --}}
    <main class="flex-1 w-full">
        <div class="max-w-md mx-auto px-5 pb-8 pt-8">
            {{-- Judul halaman --}}
            <div class="text-center mb-8">
                <h1 class="font-reenie text-[48px] sm:text-[60px] leading-[100%] text-[#171717]">tell your story</h1>
                <p class="text-gray-500 text-sm sm:text-base mt-3">kata-kata yang tak pernah terkatakan, tersampaikan lewat sebuah lagu</p>
            </div>

            {{-- ─── FLASH MESSAGE SUCCESS ─── --}}
            @if (session('success'))
                <div class="mb-6 p-4 rounded-xl bg-blue-600/10 border border-blue-900/20 text-blue-900 text-sm text-center">
                    {{ session('success') }}
                </div>
            @endif

            {{-- ─── ERROR VALIDASI ─── --}}
            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-500 text-sm">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- ─── FORM ─── --}}
            <form method="POST" action="{{ route('messages.store') }}" class="space-y-5">
                @csrf

                {{-- Input Nama Pengirim (opsional) --}}
                <div>
                    <label for="sender_name" class="block text-sm font-medium text-gray-700 mb-1.5">from <span class="text-gray-400 font-normal">(optional)</span></label>
                    <input type="text" name="sender_name" id="sender_name" value="{{ old('sender_name') }}" placeholder="your name — or stay anonymous"
                        class="w-full px-4 py-3 rounded-xl border border-[#D9D9D9] text-gray-950 placeholder:text-gray-400 focus:border-gray-950 focus:ring-1 focus:ring-gray-200 outline-none transition-colors">
                </div>

                {{-- Input Nama Penerima --}}
                <div>
                    <label for="recipient_name" class="block text-sm font-medium text-gray-700 mb-1.5">to</label>
                    <input type="text" name="recipient_name" id="recipient_name" value="{{ old('recipient_name') }}" placeholder="their name"
                        required
                        class="w-full px-4 py-3 rounded-xl border border-[#D9D9D9] text-gray-950 placeholder:text-gray-400 focus:border-gray-950 focus:ring-1 focus:ring-gray-200 outline-none transition-colors">
                </div>

                {{-- Dropdown Pilih Kelas (custom, tanpa arrow browser) --}}
                <div>
                    <label for="kelas" class="block text-sm font-medium text-gray-700 mb-1.5">kelas</label>
                    <input type="hidden" name="kelas" id="kelas" value="{{ old('kelas') }}">
                    <div id="kelas-dd" class="relative">
                        <button type="button" id="kelas-btn"
                            class="w-full px-4 py-3 rounded-xl border border-[#D9D9D9] text-left @if(old('kelas')) text-gray-950 @else text-gray-400 @endif hover:border-gray-950 focus:border-gray-950 focus:ring-1 focus:ring-gray-200 outline-none transition-colors cursor-pointer bg-white">
                            {{ old('kelas') ?: 'pilih kelas...' }}
                        </button>
                        <ul id="kelas-list"
                            class="hidden absolute z-20 mt-1 w-full max-h-64 overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg">
                            @foreach(['X PPLG 1','X PPLG 2','X PPLG 3','X PM 1','X PM 2','X AKL 1','X AKL 2','X AKL 3','X MPLB 1','X MPLB 2','X MPLB 3','XI PPLG 1','XI PPLG 2','XI PPLG 3','XI PM 1','XI PM 2','XI AKL 1','XI AKL 2','XI AKL 3','XI MPLB 1','XI MPLB 2','XI MPLB 3','XII PPLG 1','XII PPLG 2','XII PPLG 3','XII PM 1','XII PM 2','XII AKL 1','XII AKL 2','XII AKL 3','XII MPLB 1','XII MPLB 2','XII MPLB 3'] as $k)
                                <li data-value="{{ $k }}"
                                    class="px-4 py-2.5 text-gray-950 hover:bg-gray-100 cursor-pointer list-none text-sm">{{ $k }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                {{-- Textarea Isi Pesan --}}
                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700 mb-1.5">message</label>
                    <textarea name="message" id="message" rows="4"
                        placeholder="your untold words..."
                        required
                        class="w-full px-4 py-3 rounded-xl border border-[#D9D9D9] text-gray-950 placeholder:text-gray-400 focus:border-gray-950 focus:ring-1 focus:ring-gray-200 outline-none transition-colors resize-none">{{ old('message') }}</textarea>
                </div>

                {{-- Pencarian lagu via Spotify API → resolve YouTube --}}
                <div>
                    <label for="song-search" class="block text-sm font-medium text-gray-700 mb-1.5">song</label>
                    <div class="relative">
                        <input type="text" id="song-search" autocomplete="off"
                            placeholder="cari judul lagu..."
                            class="w-full px-4 py-3 rounded-xl border border-[#D9D9D9] text-gray-950 placeholder:text-gray-400 focus:border-gray-950 focus:ring-1 focus:ring-gray-200 outline-none transition-colors">
                        <div id="song-results" class="hidden absolute z-10 mt-1 w-full max-h-64 overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg"></div>
                    </div>
                    <div id="song-chip" class="hidden mt-2 flex items-center gap-2 p-2 rounded-xl border border-gray-200 bg-gray-50"></div>

                    <input type="hidden" name="spotify_track_id" id="inp-spotify-id">
                    <input type="hidden" name="song_title" id="inp-title">
                    <input type="hidden" name="song_artist" id="inp-artist">
                    <input type="hidden" name="cover_url" id="inp-cover">
                    <input type="hidden" name="youtube_video_id" id="inp-youtube-id">
                    <input type="hidden" name="clip_start_seconds" id="inp-clip-start">
                    <input type="hidden" name="clip_end_seconds" id="inp-clip-end">
                    <input type="hidden" name="duration_seconds" id="inp-duration">

                    {{-- Pemilih durasi — muncul setelah lagu dipilih. Toggle full lagu / klip custom + waveform review --}}
                    <div id="song-duration" class="hidden mt-3 rounded-xl border border-gray-200 bg-gray-50 p-3 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">durasi lagu</span>
                            <div class="flex rounded-lg border border-[#D9D9D9] bg-white p-0.5 text-xs font-medium">
                                <button type="button" id="mode-full"
                                    class="px-3 py-1.5 rounded-md bg-[#171717] text-white cursor-pointer">full lagu</button>
                                <button type="button" id="mode-clip"
                                    class="px-3 py-1.5 rounded-md text-gray-500 hover:text-gray-900 cursor-pointer">klip custom</button>
                            </div>
                        </div>

                        {{-- Row review: play + waktu --}}
                        <div class="flex items-center gap-3">
                            <button type="button" id="review-play" aria-label="putar review"
                                class="w-10 h-10 flex-none aspect-square rounded-full bg-[#171717] hover:bg-gray-800 text-white flex items-center justify-center cursor-pointer">
                                <svg id="review-ico-play" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.8a1 1 0 0 1 1.5-.9l10 6a1 1 0 0 1 0 1.7l-10 6a1 1 0 0 1-1.5-.9V2.8z"/></svg>
                                <svg id="review-ico-pause" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M6 4a1 1 0 0 1 1 1v10a1 1 0 1 1-2 0V5a1 1 0 0 1 1-1zm8 0a1 1 0 0 1 1 1v10a1 1 0 1 1-2 0V5a1 1 0 0 1 1-1z"/></svg>
                            </button>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <span id="review-time" class="text-xs text-gray-500 tabular-nums">0:00 / 0:00</span>
                                    <span id="clip-label" class="text-xs font-semibold text-gray-700">full lagu</span>
                                </div>
                            </div>
                        </div>

                        {{-- Music trimmer ala Instagram Stories: strip (waveform + kotak pilihan + handle) bergerak, playhead tetap di tengah --}}
                        <div id="clip-waveform" class="relative h-16 rounded-2xl bg-gray-100 overflow-hidden cursor-pointer select-none touch-none">
                            <div id="wave-strip" class="absolute inset-y-0 left-0 will-change-transform" style="width:100%">
                                <canvas id="clip-wave-canvas" class="absolute inset-0 h-full" style="width:100%" width="600" height="128"></canvas>
                                <div id="clip-selection" class="absolute inset-y-1 left-0 rounded-xl bg-white/75 shadow-[0_2px_10px_rgba(0,0,0,0.18)] ring-1 ring-black/5 will-change-transform cursor-grab touch-none" style="display:none"></div>
                                <div id="wave-handle-l" class="absolute inset-y-1 left-0 w-7 z-10 cursor-ew-resize touch-none hidden will-change-transform" style="transform:translateX(-50%)">
                                    <div class="absolute inset-y-0 w-7 rounded-lg bg-white shadow-[0_2px_8px_rgba(0,0,0,0.22)] flex items-center justify-center gap-[3px]">
                                        <span class="w-0.5 h-2.5 rounded-full bg-gray-400"></span>
                                        <span class="w-0.5 h-2.5 rounded-full bg-gray-400"></span>
                                        <span class="w-0.5 h-2.5 rounded-full bg-gray-400"></span>
                                    </div>
                                </div>
                                <div id="wave-handle-r" class="absolute inset-y-1 left-0 w-7 z-10 cursor-ew-resize touch-none hidden will-change-transform" style="transform:translateX(-50%)">
                                    <div class="absolute inset-y-0 w-7 rounded-lg bg-white shadow-[0_2px_8px_rgba(0,0,0,0.22)] flex items-center justify-center gap-[3px]">
                                        <span class="w-0.5 h-2.5 rounded-full bg-gray-400"></span>
                                        <span class="w-0.5 h-2.5 rounded-full bg-gray-400"></span>
                                        <span class="w-0.5 h-2.5 rounded-full bg-gray-400"></span>
                                    </div>
                                </div>
                                <div id="clip-playhead" class="absolute inset-y-0 left-0 w-0.5 bg-red-500 z-[5] pointer-events-none will-change-transform" style="transform:translateX(0px) translateX(-50%)">
                                    <span class="absolute top-0 left-1/2 -translate-x-1/2 w-1.5 h-2 rounded-b-full bg-amber-400"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Label waktu di bawah waveform --}}
                        <div class="flex items-center justify-between text-[11px] text-gray-500 tabular-nums">
                            <span id="wave-start-label">0:00</span>
                            <span id="wave-dur-label" class="font-semibold text-gray-700">full lagu</span>
                            <span id="wave-end-label">0:30</span>
                        </div>
                    </div>
                </div>

                {{-- Tombol Submit — aktif hanya setelah lagu dipilih --}}
                <div class="pt-2">
                    <button type="submit" id="submit-btn" disabled
                        class="w-full py-3 px-6 rounded-xl bg-[#171717] text-white font-semibold transition-colors disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer enabled:hover:bg-gray-800">
                        submit
                    </button>
                </div>
            </form>
        </div>
    </main>

    {{-- ─── FOOTER ─── --}}
    <footer class="border-t border-[#E9E9E9] py-6">
        <p class="text-center text-gray-500 text-xs">Artifact studios &copy; {{ date('Y') }} &mdash; SkanidaSong</p>
    </footer>

    {{-- ─── JS: DROPDOWN KELAS CUSTOM ─── --}}
    <script>
        (function () {
            var dd = document.getElementById('kelas-dd');
            if (!dd) return;
            var btn = document.getElementById('kelas-btn');
            var list = document.getElementById('kelas-list');
            var input = document.getElementById('kelas');

            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                list.classList.toggle('hidden');
            });

            list.querySelectorAll('[data-value]').forEach(function (li) {
                li.addEventListener('click', function () {
                    input.value = li.getAttribute('data-value');
                    btn.textContent = li.getAttribute('data-value');
                    btn.classList.remove('text-gray-400');
                    btn.classList.add('text-gray-950');
                    list.classList.add('hidden');
                });
            });

            document.addEventListener('click', function () {
                list.classList.add('hidden');
            });
        })();
    </script>
</body>

</html>