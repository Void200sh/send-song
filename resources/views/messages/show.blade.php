{{-- ─── HALAMAN DETAIL SATU PESAN ─── --}}
{{-- Ini halaman yang muncul kalo user klik salah satu kartu di halaman browse --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Story - SkanidaSong SMK</title>
    <link rel="icon" type="image/png" sizes="128x128" href="/favicon.png">
    {{-- Font dimuat lokal (same-origin) supaya html-to-image bisa meng-embed-nya
       ke gambar PNG "save as png". CDN fonts.bunny.net cross-origin gagal dibaca
       (SecurityError/CORS) → font fallback → tulisan tumpuk di gambar. --}}
    @include('partials.fonts')
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

        {{-- Kartu detail besar (tema kartu dari kolom theme) --}}
        <div class="relative overflow-hidden border border-[#E9E9E9] rounded-xl p-6 sm:p-8 theme-{{ $message->theme ?: 'classic' }}">
            @include('partials.theme-decor', ['theme' => $message->theme])
            {{-- Nama pengirim & penerima (font Reenie Beanie identik) --}}
            <div class="mb-2">
                <p class="font-reenie text-[40px] sm:text-[48px] leading-[100%] text-[#171717]">from: {!! \App\Support\EmojiText::small($message->sender_name ?: 'anonymous') !!}</p>
                <p class="font-reenie text-[40px] sm:text-[48px] leading-[100%] text-[#171717]">to: {!! \App\Support\EmojiText::small($message->recipient_name) !!}</p>
            </div>
            {{-- Kelas + waktu + views --}}
            <div class="text-xs text-gray-500 mb-6 flex items-center gap-2">
                <span>{{ $message->kelas }} &bull; {{ $message->created_at->diffForHumans() }} &bull; {{ $message->created_at->format('d M Y, H:i') }}</span>
                <span class="inline-flex items-center gap-0.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                    <span>{{ $message->views }}</span>
                </span>
                <span class="inline-flex items-center gap-0.5 text-gray-400">
                    {{ $message->unique_views }} pengunjung
                </span>
            </div>

            {{-- Isi pesan lengkap (font Reenie Beanie, sama seperti from/to) --}}
            <p class="font-reenie text-[28px] leading-[100%] text-[#171717] mb-6">{!! \App\Support\EmojiText::small($message->message) !!}</p>

            {{-- Blok lagu: judul & artis SELALU tampil kalau ada lagu. Player YouTube kalau ada id-nya, link Spotify kalau ada. --}}
            @if ($message->song_title || $message->youtube_video_id || $message->spotify_track_id)
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
                @else
                    {{-- Lagu tanpa YouTube ID: judul & artis tetap tampil + link Spotify kalau ada id-nya --}}
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <div class="flex items-center gap-3">
                            @if ($message->cover_url)
                                <img src="{{ $message->cover_url }}" class="w-12 h-12 rounded-lg object-cover" alt="cover">
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-gray-900 truncate">{{ $message->song_title }}</p>
                                @if ($message->song_artist)
                                    <p class="text-xs text-gray-500 truncate">{{ $message->song_artist }}</p>
                                @endif
                            </div>
                            @if ($message->spotify_track_id)
                                <a href="https://open.spotify.com/track/{{ $message->spotify_track_id }}" target="_blank"
                                    class="shrink-0 inline-flex items-center gap-1.5 text-sm text-green-600 hover:underline">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16zm3.6 11.6a.5.5 0 0 1-.7.2c-1.9-1.2-4.4-1.5-7.2-.8a.5.5 0 0 1-.2-1c3-.7 5.8-.4 7.9 1a.5.5 0 0 1 .2.6zm1-2.2a.6.6 0 0 1-.8.3c-2.2-1.4-5.6-1.7-8.2-1a.6.6 0 0 1-.4-1.2c3-.8 6.8-.5 9.2 1a.6.6 0 0 1 .2.9zm.1-2.3c-2.7-1.6-7-1.7-9.6-1a.7.7 0 0 1-.4-1.4c3-1 7.5-.8 10.5 1a.7.7 0 0 1-.5 1.3z"/></svg>
                                    buka
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            @endif
        </div>

        {{-- Reaksi emoji ala WhatsApp --}}
        @include('partials.reactions', ['msg' => $message, 'mine' => $myReactions])

        {{-- Tombol lapor — kirim laporan ke admin kalau pesan ini tidak pantas --}}
        <div class="mt-4">
            <button type="button" data-report data-report-id="{{ $message->id }}"
                class="inline-flex items-center gap-1.5 text-xs text-gray-400 hover:text-red-600 transition-colors cursor-pointer bg-transparent border-0 p-0">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.3 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.7 3.86a2 2 0 0 0-3.4 0z"/></svg>
                laporkan pesan ini
            </button>
        </div>

        {{-- ─── BALASAN (thread mini) ─── --}}
        <div class="mt-8">
            <div class="flex items-center gap-2 mb-4">
                <h2 class="font-reenie text-[32px] leading-[100%] text-[#171717]">balasan</h2>
                @if ($message->replies->count() > 0)
                    <span class="text-xs font-semibold bg-[#171717]/5 border border-[#E9E9E9] text-gray-600 rounded-full px-2.5 py-1">
                        {{ $message->replies->count() }} balasan
                    </span>
                @endif
            </div>

            @if (session('reply_success'))
                <div class="mb-4 p-3 rounded-xl bg-blue-600/10 border border-blue-900/20 text-blue-900 text-sm">
                    {{ session('reply_success') }}
                </div>
            @endif

            {{-- Daftar balasan --}}
            @if ($message->replies->count() > 0)
                <div class="space-y-4">
                    @foreach ($message->replies as $reply)
                        @php
                            $replyCounts = $reply->reactionCounts();
                            $replyMine = $myReplyReactions[$reply->id] ?? [];
                        @endphp
                        <div class="flex gap-3">
                            {{-- Avatar dengan inisial — warna konsisten per nama (hash) biar gak ganti-ganti --}}
                            <div class="w-9 h-9 shrink-0 rounded-full border border-[#E9E9E9] flex items-center justify-center text-sm font-bold shadow-sm"
                                style="background:{{ $reply->sender_name ? 'hsl(' . (crc32($reply->sender_name) % 360) . ' 60% 93%)' : '#f3f4f6' }};color:{{ $reply->sender_name ? 'hsl(' . (crc32($reply->sender_name) % 360) . ' 40% 32%)' : '#9ca3af' }}">
                                {{ mb_strtoupper(mb_substr($reply->sender_name ?: '?', 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0 bg-gray-50 border border-[#E9E9E9] rounded-2xl rounded-tl-md px-4 py-3">
                                <div class="flex items-baseline gap-2 flex-wrap">
                                    <span class="text-sm font-semibold text-gray-900">{{ $reply->sender_name ?: 'anonim' }}</span>
                                    <span class="text-[11px] text-gray-400">{{ $reply->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="text-sm text-gray-700 mt-1 break-words leading-relaxed">{!! \App\Support\EmojiText::small($reply->body) !!}</p>

                                {{-- Reaksi emoji per balasan (toggle ala WhatsApp) — chip selalu dirender, disembunyikan saat count 0 & tidak aktif --}}
                                <div class="mt-2.5 flex flex-wrap items-center gap-1.5" data-reply-reactions data-reply-id="{{ $reply->id }}">
                                    @foreach (\App\Models\Message::REACTION_EMOJIS as $emoji)
                                        @php
                                            $count = $replyCounts[$emoji] ?? 0;
                                            $active = in_array($emoji, $replyMine, true);
                                        @endphp
                                        <button type="button" data-react-reply data-reply-id="{{ $reply->id }}" data-emoji="{{ $emoji }}"
                                            data-active="{{ $active ? '1' : '0' }}"
                                            aria-pressed="{{ $active ? 'true' : 'false' }}"
                                            title="{{ $active ? 'Batalkan reaksi' : 'Beri reaksi' }}"
                                            class="{{ $count === 0 && ! $active ? 'hidden' : '' }} flex items-center gap-1 px-2 py-0.5 rounded-full border text-xs transition-all cursor-pointer select-none
                                                {{ $active
                                                    ? 'bg-[#171717] border-[#171717] text-white shadow-sm'
                                                    : 'bg-white/80 border-[#E9E9E9] text-gray-500 hover:border-[#171717] hover:text-gray-900' }}">
                                            <span class="text-sm leading-none">{{ $emoji }}</span>
                                            <span data-reply-react-count class="font-semibold tabular-nums">{{ $count }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 bg-gray-50 border border-dashed border-[#E9E9E9] rounded-2xl">
                    <p class="text-sm text-gray-400">belum ada balasan — jadilah yang pertama 💬</p>
                </div>
            @endif

            {{-- Form kirim balasan (redesain: avatar + nama + textarea dengan counter + tombol kirim) --}}
            <form method="POST" action="{{ route('messages.reply', $message) }}" class="mt-6 bg-white border border-[#E9E9E9] rounded-2xl p-4 shadow-sm" id="reply-form">
                @csrf
                <div class="flex gap-3">
                    <div data-reply-avatar class="w-9 h-9 shrink-0 rounded-full bg-[#171717]/5 border border-[#E9E9E9] flex items-center justify-center text-[#171717] text-sm font-bold">
                        {{ old('sender_name') ? mb_strtoupper(mb_substr(old('sender_name'), 0, 1)) : '?' }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <input type="text" name="sender_name" id="reply-name" value="{{ old('sender_name') }}" data-reply-avatar-input
                            placeholder="nama kamu (opsional)" maxlength="255"
                            class="w-full px-3 py-2 rounded-xl border border-[#D9D9D9] text-sm text-gray-950 placeholder:text-gray-400 focus:border-gray-950 focus:ring-1 focus:ring-gray-200 outline-none transition-colors bg-white">
                        <div class="relative mt-2">
                            <textarea name="body" id="reply-body" rows="3" required maxlength="1000"
                                placeholder="tulis balasanmu di sini..."
                                data-reply-counter
                                class="w-full px-3 py-2.5 pr-16 rounded-xl border border-[#D9D9D9] text-sm text-gray-950 placeholder:text-gray-400 focus:border-gray-950 focus:ring-1 focus:ring-gray-200 outline-none transition-colors resize-none bg-white">{{ old('body') }}</textarea>
                            <span data-reply-counter-label
                                class="absolute bottom-2.5 right-3 text-[10px] text-gray-300 tabular-nums pointer-events-none">0/1000</span>
                            @error('body')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex items-center justify-between mt-3">
                            <p class="text-[11px] text-gray-400">balasanmu akan tampil di bawah pesan ini</p>
                            <button type="submit"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#171717] text-white text-sm font-semibold transition-colors hover:bg-gray-800 cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                kirim
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- Tombol unduh pesan jadi gambar story 9:16 --}}
        <div class="mt-4">
            {{-- Tombol unduh gambar story 9:16 + tombol share (bagikan LINK saja) --}}
            <div class="flex flex-col sm:flex-row gap-2">
                <button type="button" data-story-download
                    class="flex-1 py-3 px-6 rounded-xl bg-[#171717] text-white font-semibold transition-colors disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer enabled:hover:bg-gray-800 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                    save as png
                </button>
                <button type="button" data-story-share
                    data-share-url="{{ url()->current() }}"
                    data-share-recipient="{{ $message->recipient_name }}"
                    class="flex-1 py-3 px-6 rounded-xl border border-[#171717] text-gray-950 font-semibold transition-colors disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer enabled:hover:bg-gray-50 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8M16 6l-4-4-4 4M12 2v13"/></svg>
                    share
                </button>
            </div>
            <p data-story-error class="hidden w-full sm:basis-full text-red-500 text-sm mt-2 text-center"></p>
        </div>
    </main>

    {{-- ─── KARTU STORY 9:16 (1080x1920) — DI-CAPTURE JADI GAMBAR (save as png / share) ─── --}}
    @include('partials.story-art', ['msg' => $message])

    {{-- ─── JS: CHAR COUNTER + AVATAR LIVE FORM BALASAN ─── --}}
    <script>
        (function () {
            var area = document.querySelector('[data-reply-counter]');
            var label = document.querySelector('[data-reply-counter-label]');
            if (area && label) {
                var update = function () {
                    label.textContent = area.value.length + '/1000';
                };
                area.addEventListener('input', update);
                update();
            }

            // Avatar form ikut update mengikuti nama yang diketik (live)
            var nameInput = document.querySelector('[data-reply-avatar-input]');
            var avatar = document.querySelector('[data-reply-avatar]');
            if (nameInput && avatar) {
                nameInput.addEventListener('input', function () {
                    var initial = nameInput.value.trim().charAt(0);
                    avatar.textContent = initial ? initial.toUpperCase() : '?';
                });
            }
        })();
    </script>

    {{-- ─── FOOTER ─── --}}
    <footer class="border-t border-[#E9E9E9] py-6 mt-auto">
        <p class="text-center text-gray-500 text-xs">Artifact studios &copy; {{ date('Y') }} &mdash; SkanidaSong</p>
    </footer>
</body>

</html>
