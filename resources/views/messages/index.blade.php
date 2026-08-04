{{-- ─── HALAMAN BROWSE / FEED PESAN ─── --}}
{{-- Ini halaman buat liat semua pesan yang udah dikirim, lengkap dengan search & filter --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Browse - SendTheSong SMK</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=reenie-beanie:400|plus-jakarta-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#FFFFFF] text-gray-950 font-sans min-h-screen flex flex-col">
    {{-- ─── HEADER — SAMA KAYAK WELCOME, TAPI LINK NYA TERBALIK ─── --}}
    {{-- Bedanya: di sini "browse" jadi tombol hitam (aktif), "tell your story" jadi link biasa --}}
    <header class="border-b border-[#E9E9E9]">
        <div class="max-w-7xl mx-auto px-5 sm:px-8 lg:px-20 flex items-center justify-between h-14">
            <a href="{{ url('/') }}" class="font-reenie text-[28px] sm:text-[36px] leading-[100%] text-[#171717]">SendTheSong</a>
            <nav class="flex items-center gap-4 sm:gap-6">
                <a href="{{ route('messages.index') }}"
                    class="text-sm font-semibold text-white bg-[#171717] px-4 py-2 rounded-xl hover:bg-gray-800 transition-colors">browse</a>
                <a href="{{ url('/') }}"
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
                    {{-- Dropdown filter kelas — ngambil opsi dari $kelasList yang dikirim controller --}}
                    <div class="w-full sm:w-fit">
                        <select name="kelas" id="kelas"
                            class="w-full px-4 py-3 rounded-xl border border-[#D9D9D9] text-gray-950 focus:border-gray-950 focus:ring-1 focus:ring-gray-200 outline-none transition-colors appearance-none cursor-pointer bg-white">
                            <option value="">all classes</option>
                            @foreach ($kelasList as $k)
                                {{-- Pake ternary: kalo $selectedKelas sama dengan opsi, tambahin 'selected' --}}
                                <option value="{{ $k }}" {{ $selectedKelas == $k ? 'selected' : '' }}>{{ $k }}</option>
                            @endforeach
                        </select>
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
                    {{-- ─── SATU CARD PESAN ─── --}}
                    <div class="border border-[#E9E9E9] rounded-xl p-5 transition-colors hover:border-[#D9D9D9] hover:shadow-sm bg-white">
                        {{-- Nama penerima (font Reenie Beanie besar) --}}
                        <div class="mb-2">
                            <h2 class="font-reenie text-[28px] sm:text-[32px] leading-[100%] text-[#171717]">to: {{ $msg->recipient_name }}</h2>
                        </div>
                        {{-- Kelas + waktu (relatif, ex: "XI PPLG 1 • 2 hours ago") --}}
                        <div class="text-xs text-gray-500 mb-3">
                            {{ $msg->kelas }} &bull; {{ $msg->created_at->diffForHumans() }}
                        </div>
                        {{-- Isi pesan --}}
                        <p class="text-sm text-gray-600 leading-relaxed mb-4">{{ $msg->message }}</p>
                        {{-- Spotify player — cuma nongol kalo spotify_track_id ADA (gak null) --}}
                        @if ($msg->spotify_track_id)
                            <div class="rounded-lg overflow-hidden">
                                {{-- Iframe Spotify — height 80px = compact player --}}
                                <iframe
                                    src="https://open.spotify.com/embed/track/{{ $msg->spotify_track_id }}?utm_source=generator"
                                    width="100%"
                                    height="80"
                                    frameborder="0"
                                    allowfullscreen
                                    allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"
                                    loading="lazy">
                                </iframe>
                            </div>
                        @endif
                    </div>
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
        <p class="text-center text-gray-500 text-xs">SMK Negeri 1 &copy; {{ date('Y') }} &mdash; SendTheSong</p>
    </footer>
</body>

</html>
