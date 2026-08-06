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
                </div>

                {{-- Tombol Submit --}}
                <div class="pt-2">
                    <button type="submit"
                        class="w-full py-3 px-6 rounded-xl bg-[#171717] hover:bg-gray-800 text-white font-semibold transition-colors">
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