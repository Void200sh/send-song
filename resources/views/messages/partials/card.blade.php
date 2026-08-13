{{-- ─── SATU CARD PESAN (halaman browse) — semuanya link ke halaman detail (kecuali elemen interaktif) ─── --}}
{{-- Dipakai di halaman browse (render awal) & fragment AJAX infinite scroll. Butuh variabel: $msg dan $mine. --}}
{{-- Tema kartu: theme-{key} dari kolom theme (null = polos/classic) --}}
<a href="{{ route('messages.show', $msg) }}" data-msg-card data-detail-url="{{ route('messages.show', $msg) }}"
    class="block relative overflow-hidden border border-[#E9E9E9] rounded-xl p-5 transition-colors hover:border-[#171717] hover:shadow-sm theme-{{ $msg->theme ?: 'classic' }} cursor-pointer">
    @include('partials.theme-decor', ['theme' => $msg->theme])
    {{-- Badge pin (dipasang admin) — tampil di pojok kanan atas, di samping tombol share --}}
    @if ($msg->is_pinned)
        <span class="absolute top-3 right-12 flex items-center gap-1 text-[11px] font-semibold text-white bg-[#171717]/90 rounded-full px-2 py-1 shadow-sm">📌 pinned</span>
    @endif
    {{-- Tombol lapor — laporkan pesan yang tidak pantas ke admin (klik aman, tidak membuka detail). --}}
    <button type="button" data-report
        data-report-id="{{ $msg->id }}"
        title="laporkan pesan ini"
        aria-label="laporkan pesan ini"
        class="absolute top-14 right-3 z-10 w-8 h-8 flex items-center justify-center rounded-full bg-white/85 border border-[#E9E9E9] text-gray-400 hover:text-red-600 hover:border-red-300 transition-colors cursor-pointer shadow-sm backdrop-blur">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.3 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.7 3.86a2 2 0 0 0-3.4 0z"/></svg>
    </button>
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
    {{-- Kelas + waktu + views + balasan (relatif, ex: "XI PPLG 1 • 2 hours ago • 👁 12 • 💬 3") --}}
    <div class="text-xs text-gray-500 mb-3 flex items-center gap-2">
        <span>{{ $msg->kelas }} &bull; {{ $msg->created_at->diffForHumans() }}</span>
        <span class="inline-flex items-center gap-0.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
            <span>{{ $msg->views }}</span>
        </span>
        @if ($msg->replies_count > 0)
            <span class="inline-flex items-center gap-0.5" title="{{ $msg->replies_count }} balasan">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-5l-5 5v-5z"/></svg>
                <span>{{ $msg->replies_count }}</span>
            </span>
        @endif
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
    @include('partials.reactions', ['msg' => $msg, 'mine' => $mine])
</a>
