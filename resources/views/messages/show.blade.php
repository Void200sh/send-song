{{-- ─── HALAMAN DETAIL SATU PESAN ─── --}}
{{-- Ini halaman yang muncul kalo user klik salah satu kartu di halaman browse --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Story - SkanidaSong SMK</title>
    <link rel="icon" type="image/png" sizes="128x128" href="/favicon.png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#FFFFFF] text-gray-950 font-sans min-h-screen flex flex-col">
    {{-- ─── HEADER — SAMA KAYAK HALAMAN LAIN ─── --}}
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
    <main class="flex-1 max-w-3xl mx-auto w-full px-5 sm:px-8 lg:px-20 py-8">
        {{-- Tombol kembali ke browse --}}
        <a href="{{ route('messages.index') }}"
            class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-950 transition-colors mb-6">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            back to browse
        </a>

        {{-- Kartu detail besar --}}
        <div class="border border-[#E9E9E9] rounded-xl p-6 sm:p-8 bg-white">
            {{-- Nama pengirim & penerima (font Reenie Beanie identik) --}}
            <div class="mb-2">
                <p class="font-reenie text-[40px] sm:text-[48px] leading-[100%] text-[#171717]">from: {!! \App\Support\EmojiText::small($message->sender_name ?: 'anonymous') !!}</p>
                <p class="font-reenie text-[40px] sm:text-[48px] leading-[100%] text-[#171717]">to: {!! \App\Support\EmojiText::small($message->recipient_name) !!}</p>
            </div>
            {{-- Kelas + waktu --}}
            <div class="text-xs text-gray-500 mb-6">
                {{ $message->kelas }} &bull; {{ $message->created_at->diffForHumans() }} &bull; {{ $message->created_at->format('d M Y, H:i') }}
            </div>

            {{-- Isi pesan lengkap (font Reenie Beanie, sama seperti from/to) --}}
            <p class="font-reenie text-[28px] leading-[100%] text-[#171717] mb-6">{!! \App\Support\EmojiText::small($message->message) !!}</p>

            {{-- Player penuh kalo ada YouTube ID --}}
            @if ($message->youtube_video_id)
                <div data-player-card data-video-id="{{ $message->youtube_video_id }}"
                    data-clip-start="{{ $message->clip_start_seconds }}"
                    data-clip-end="{{ $message->clip_end_seconds }}"
                    class="rounded-xl border border-gray-200 bg-gray-50 p-4 transition-shadow">
                    <div class="flex items-center gap-3">
                        @if ($message->cover_url)
                            <img src="{{ $message->cover_url }}" class="w-12 h-12 rounded-lg object-cover" alt="cover">
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $message->song_title }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ $message->song_artist }}</p>
                        </div>
                        <button type="button" data-play
                            class="w-11 h-11 shrink-0 rounded-full bg-[#171717] hover:bg-gray-800 text-white flex items-center justify-center">
                            <svg data-icon-play class="w-4 h-4 ml-0.5" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.8a1 1 0 0 1 1.5-.9l10 6a1 1 0 0 1 0 1.7l-10 6a1 1 0 0 1-1.5-.9V2.8z"/></svg>
                            <svg data-icon-pause class="hidden w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M5 3h3v14H5V3zm7 0h3v14h-3V3z"/></svg>
                        </button>
                    </div>
                    <div class="flex items-center gap-2 mt-4">
                        <span data-current class="text-[11px] text-gray-500 w-8">0:00</span>
                        <div class="relative flex-1 h-1.5 bg-gray-200 rounded-full cursor-pointer">
                            <div data-progress class="absolute left-0 top-0 h-full bg-gray-900 rounded-full" style="width:0%"></div>
                            <input data-seekbar type="range" min="0" max="1000" value="0"
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        </div>
                        <span data-duration class="text-[11px] text-gray-500 w-8 text-right">{{ $message->display_duration }}</span>
                    </div>
                    <a data-fallback href="https://open.spotify.com/track/{{ $message->spotify_track_id }}"
                        target="_blank" class="hidden mt-2 text-xs text-green-600 hover:underline">
                        video tidak tersedia — buka di Spotify
                    </a>
                </div>
            @elseif ($message->spotify_track_id)
                {{-- Fallback: link Spotify kalo gak ada YouTube ID --}}
                <a href="https://open.spotify.com/track/{{ $message->spotify_track_id }}" target="_blank"
                    class="inline-flex items-center gap-1.5 text-sm text-green-600 hover:underline">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16zm3.6 11.6a.5.5 0 0 1-.7.2c-1.9-1.2-4.4-1.5-7.2-.8a.5.5 0 0 1-.2-1c3-.7 5.8-.4 7.9 1a.5.5 0 0 1 .2.6zm1-2.2a.6.6 0 0 1-.8.3c-2.2-1.4-5.6-1.7-8.2-1a.6.6 0 0 1-.4-1.2c3-.8 6.8-.5 9.2 1a.6.6 0 0 1 .2.9zm.1-2.3c-2.7-1.6-7-1.7-9.6-1a.7.7 0 0 1-.4-1.4c3-1 7.5-.8 10.5 1a.7.7 0 0 1-.5 1.3z"/></svg>
                    buka lagu di Spotify
                </a>
            @endif
        </div>

        {{-- Tombol unduh pesan jadi gambar story 9:16 --}}
        <div class="mt-4">
            <button type="button" data-story-download
                class="w-full py-3 px-6 rounded-xl bg-[#171717] text-white font-semibold transition-colors disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer enabled:hover:bg-gray-800 flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                save as png
            </button>
            <p data-story-error class="hidden text-red-500 text-sm mt-2 text-center"></p>
        </div>
    </main>

    {{-- ─── KARTU STORY 9:16 (1080x1920) — DI-CAPTURE JADI GAMBAR INSTAGRAM STORY ─── --}}
    {{-- Disembunyikan di luar layar (bukan display:none, biar bisa di-render), --}}
    {{-- desain konsisten sama kartu detail di atas. --}}
    <div data-story-art data-story-id="{{ $message->id }}"
        class="fixed top-0 left-0 bg-[#FFFFFF] flex items-center justify-center px-24 text-center overflow-hidden opacity-0 pointer-events-none select-none"
        style="width:1080px;height:1920px">
        {{-- Wrapper konten — diukur & di-scale otomatis oleh JS biar selalu muat di 1920px --}}
        <div data-story-inner class="w-full flex flex-col items-center justify-center">
            <p class="font-reenie text-[48px] leading-[100%] text-[#171717] mb-14" style="line-height:1.8">SkanidaSong.my.id</p>

            <p class="font-reenie text-[72px] text-[#171717]" style="line-height:1.8">from: {!! \App\Support\EmojiText::small($message->sender_name ?: 'anonymous') !!}</p>
            <p class="font-reenie text-[72px] text-[#171717] mb-10" style="line-height:1.8">to: {!! \App\Support\EmojiText::small($message->recipient_name) !!}</p>

            <p class="text-[24px] text-gray-500 mb-12">{{ $message->kelas }} &bull; {{ $message->created_at->format('d M Y') }}</p>

            <p class="font-reenie text-[56px] text-[#171717] max-w-full" style="line-height:1.8">{!! \App\Support\EmojiText::small($message->message) !!}</p>

            @if ($message->song_title)
                <div class="mt-10 flex items-center gap-6 bg-gray-50 border border-[#E9E9E9] rounded-3xl px-8 py-5">
                    @if ($message->cover_url)
                        <img data-story-cover src="{{ $message->cover_url }}" class="w-20 h-20 rounded-2xl object-cover" alt="cover">
                    @endif
                    <div class="text-left">
                        <p class="text-[30px] font-bold text-gray-950 max-w-[560px] truncate" style="line-height:1.4">{{ $message->song_title }}</p>
                        <p class="text-[24px] text-gray-500" style="line-height:1.4">{{ $message->song_artist }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ─── FOOTER ─── --}}
    <footer class="border-t border-[#E9E9E9] py-6 mt-auto">
        <p class="text-center text-gray-500 text-xs">Artifact studios &copy; {{ date('Y') }} &mdash; SkanidaSong</p>
    </footer>
</body>

</html>
