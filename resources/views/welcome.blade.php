{{-- ─── DOKUMEN HTML ─── --}}
{{-- Ini adalah halaman utama (landing page) SendTheSong — isinya stats, marquee, dan form kirim pesan --}}
<!DOCTYPE html>
{{-- app()->getLocale() ngambil bahasa dari config Laravel (default: 'id' atau 'en') --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    {{-- Viewport biar responsive di HP --}}
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SendTheSong - SMK</title>
    {{-- Preconnect ke Google Fonts biar loading font lebih cepet --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    {{-- Load 2 font: Reenie Beanie (font dekoratif buat judul) sama Plus Jakarta Sans (font utama) --}}
    <link href="https://fonts.bunny.net/css?family=reenie-beanie:400|plus-jakarta-sans:400,500,600,700" rel="stylesheet" />
    {{-- @vite — compile dan load CSS + JS pake Vite (bundler modern pengganti Mix) --}}
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
            <a href="{{ url('/') }}" class="font-reenie text-[28px] sm:text-[36px] leading-[100%] text-[#171717]">SendTheSong</a>
            <nav class="flex items-center gap-4 sm:gap-6">
                {{-- Link ke halaman browse — di landing page, link ini tampil biasa (gak aktif) --}}
                <a href="{{ route('messages.index') }}"
                    class="text-sm text-gray-500 hover:text-gray-950 transition-colors">browse</a>
                {{-- Link ke landing page sendiri — tampil sebagai tombol hitam "tell your story" --}}
                <a href="{{ url('/') }}"
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
                <h1 class="font-reenie text-[48px] sm:text-[60px] leading-[100%] text-[#171717]">SendTheSong</h1>
                <p class="text-gray-500 text-sm sm:text-base mt-3">a bunch of the untold words, sent through the song</p>
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
        </div>

        {{-- ─── MARQUEE 1: NAMA & KELAS (KANAN KE KIRI) ─── --}}
        {{-- Marquee ini nampilin "to: [nama] — [kelas]" jalan terus ke kiri --}}
        <div class="border-y border-[#E9E9E9] overflow-hidden py-4 mb-8">
            {{-- animate-marquee = animasi custom dari app.css (translate dari 0 ke -50%) --}}
            <div class="flex animate-marquee whitespace-nowrap">
                {{-- Div 1: konten asli --}}
                <div class="flex gap-10 shrink-0">
                    @foreach ($marqueeMessages as $msg)
                        <span class="text-sm text-gray-600">
                            to: {{ $msg->recipient_name }}
                            <span class="text-gray-300 mx-2">&mdash;</span>
                            {{ $msg->kelas }}
                        </span>
                    @endforeach
                </div>
                {{-- Div 2: DUPLIKAT konten (biar loop mulus, gak ada jeda kosong) --}}
                <div class="flex gap-10 shrink-0">
                    @foreach ($marqueeMessages as $msg)
                        <span class="text-sm text-gray-600">
                            to: {{ $msg->recipient_name }}
                            <span class="text-gray-300 mx-2">&mdash;</span>
                            {{ $msg->kelas }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ─── MARQUEE 2: ISI PESAN (KIRI KE KANAN) ─── --}}
        {{-- Marquee ini nampilin isi pesan dipotong 60 karakter, jalannya KE KANAN (reverse) --}}
        <div class="border-b border-[#E9E9E9] overflow-hidden py-4 mb-8">
            {{-- animate-marquee-reverse = kebalikan dari marquee biasa --}}
            <div class="flex animate-marquee-reverse whitespace-nowrap">
                <div class="flex gap-10 shrink-0">
                    @foreach ($marqueeMessages as $msg)
                        {{-- Str::limit potong teks jadi maksimal 60 karakter --}}
                        <span class="text-sm text-gray-500 italic">
                            "{{ \Illuminate\Support\Str::limit($msg->message, 60) }}"
                        </span>
                    @endforeach
                </div>
                {{-- Duplikat lagi biar infinite loop mulus --}}
                <div class="flex gap-10 shrink-0">
                    @foreach ($marqueeMessages as $msg)
                        <span class="text-sm text-gray-500 italic">
                            "{{ \Illuminate\Support\Str::limit($msg->message, 60) }}"
                        </span>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ─── FORM KIRIM PESAN ─── --}}
        <div class="max-w-md mx-auto px-5 pb-8">
            {{-- ─── FLASH MESSAGE SUCCESS ─── --}}
            {{-- session('success') muncul kalo abis kirim pesan berhasil (dari controller store()) --}}
            @if (session('success'))
                <div class="mb-6 p-4 rounded-xl bg-blue-600/10 border border-blue-900/20 text-blue-900 text-sm text-center">
                    {{ session('success') }}
                </div>
            @endif

            {{-- ─── ERROR VALIDASI ─── --}}
            {{-- $errors->any() — kalo ada error validasi dari $request->validate(), tampilin di sini --}}
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
            {{-- method POST, action ke route messages.store --}}
            <form method="POST" action="{{ route('messages.store') }}" class="space-y-5">
                {{-- @csrf = token keamanan Laravel, WAJIB di setiap form POST biar gak kena CSRF attack --}}
                @csrf

                {{-- Input Nama Pengirim (opsional — kalo dikosongin = anonim) --}}
                <div>
                    <label for="sender_name" class="block text-sm font-medium text-gray-700 mb-1.5">from <span class="text-gray-400 font-normal">(optional)</span></label>
                    <input type="text" name="sender_name" id="sender_name" placeholder="your name — or stay anonymous"
                        class="w-full px-4 py-3 rounded-xl border border-[#D9D9D9] text-gray-950 placeholder:text-gray-400 focus:border-gray-950 focus:ring-1 focus:ring-gray-200 outline-none transition-colors">
                </div>

                {{-- Input Nama Penerima --}}
                <div>
                    <label for="recipient_name" class="block text-sm font-medium text-gray-700 mb-1.5">to</label>
                    <input type="text" name="recipient_name" id="recipient_name" placeholder="their name"
                        required {{-- required = HTML5 validation, gak bakal submit kalo kosong --}}
                        class="w-full px-4 py-3 rounded-xl border border-[#D9D9D9] text-gray-950 placeholder:text-gray-400 focus:border-gray-950 focus:ring-1 focus:ring-gray-200 outline-none transition-colors">
                </div>

                {{-- Dropdown Pilih Kelas --}}
                <div>
                    <label for="kelas" class="block text-sm font-medium text-gray-700 mb-1.5">kelas</label>
                    {{-- required = wajib pilih, appearance-none = ilangin panah default biar bisa di-custom --}}
                    <select name="kelas" id="kelas" required
                        class="w-full px-4 py-3 rounded-xl border border-[#D9D9D9] text-gray-950 focus:border-gray-950 focus:ring-1 focus:ring-gray-200 outline-none transition-colors appearance-none cursor-pointer bg-white">
                        <option value="" disabled selected>pilih kelas...</option>
                        {{-- optgroup = ngelompokin opsi berdasarkan tingkat (X, XI, XII) --}}
                        <optgroup label="X">
                            <option value="X PPLG 1">X PPLG 1</option>
                            <option value="X PPLG 2">X PPLG 2</option>
                            <option value="X AKL 1">X AKL 1</option>
                            <option value="X AKL 2">X AKL 2</option>
                            <option value="X PM 1">X PM 1</option>
                            <option value="X PM 2">X PM 2</option>
                            <option value="X MPLB 1">X MPLB 1</option>
                            <option value="X MPLB 2">X MPLB 2</option>
                        </optgroup>
                        <optgroup label="XI">
                            <option value="XI PPLG 1">XI PPLG 1</option>
                            <option value="XI PPLG 2">XI PPLG 2</option>
                            <option value="XI AKL 1">XI AKL 1</option>
                            <option value="XI AKL 2">XI AKL 2</option>
                            <option value="XI PM 1">XI PM 1</option>
                            <option value="XI PM 2">XI PM 2</option>
                            <option value="XI MPLB 1">XI MPLB 1</option>
                            <option value="XI MPLB 2">XI MPLB 2</option>
                        </optgroup>
                        <optgroup label="XII">
                            <option value="XII PPLG 1">XII PPLG 1</option>
                            <option value="XII PPLG 2">XII PPLG 2</option>
                            <option value="XII AKL 1">XII AKL 1</option>
                            <option value="XII AKL 2">XII AKL 2</option>
                            <option value="XII PM 1">XII PM 1</option>
                            <option value="XII PM 2">XII PM 2</option>
                            <option value="XII MPLB 1">XII MPLB 1</option>
                            <option value="XII MPLB 2">XII MPLB 2</option>
                        </optgroup>
                    </select>
                </div>

                {{-- Textarea Isi Pesan --}}
                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700 mb-1.5">message</label>
                    <textarea name="message" id="message" rows="4"
                        placeholder="your untold words..."
                        required
                        class="w-full px-4 py-3 rounded-xl border border-[#D9D9D9] text-gray-950 placeholder:text-gray-400 focus:border-gray-950 focus:ring-1 focus:ring-gray-200 outline-none transition-colors resize-none"></textarea>
                </div>

                {{-- Input Link Spotify (opsional) --}}
                <div>
                    <label for="spotify_url" class="block text-sm font-medium text-gray-700 mb-1.5">song (spotify link)</label>
                    <input type="text" name="spotify_url" id="spotify_url"
                        placeholder="https://open.spotify.com/track/..."
                        class="w-full px-4 py-3 rounded-xl border border-[#D9D9D9] text-gray-950 placeholder:text-gray-400 focus:border-gray-950 focus:ring-1 focus:ring-gray-200 outline-none transition-colors">
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
        {{-- date('Y') nampilin tahun sekarang otomatis --}}
        <p class="text-center text-gray-500 text-xs">SMK Negeri 1 &copy; {{ date('Y') }} &mdash; SendTheSong</p>
    </footer>
</body>

</html>
