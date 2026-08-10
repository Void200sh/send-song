{{-- ─── KARTU MARQUEE (ala sendthesong.xyz) — dipakai berulang di baris kartu berjalan ─── --}}
{{-- Parameter: $msg (model Message). Tema kartu ikut dipakai (theme-{key}). --}}
<div class="w-[300px] shrink-0 relative overflow-hidden flex flex-col justify-between gap-3 rounded-xl border border-[#E9E9E9] shadow-sm p-4 theme-{{ $msg->theme ?: 'classic' }}">
    @include('partials.theme-decor', ['theme' => $msg->theme])

    {{-- Header: cover lagu + judul + artis --}}
    @if ($msg->song_title)
        <div class="flex items-center gap-2.5">
            @if ($msg->cover_url)
                <img src="{{ $msg->cover_url }}" class="w-10 h-10 rounded-lg object-cover shrink-0" alt="" loading="lazy">
            @endif
            <div class="min-w-0 flex-1">
                <p class="text-[13px] font-semibold text-gray-950 truncate">{{ $msg->song_title }}</p>
                <p class="text-[11px] text-gray-700 truncate">{{ $msg->song_artist }}</p>
            </div>
        </div>
    @else
        {{-- Pesan lama tanpa lagu — placeholder biar kartunya tetap seimbang --}}
        <div class="flex items-center gap-2 text-xs text-gray-500">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M18 3a1 1 0 0 0-1.2-.97l-6 1.2A1 1 0 0 0 10 4.2v9.05A3 3 0 1 0 11.5 16V6.4l4-.8v4.65A3 3 0 1 0 17 13V3z"/></svg>
            pesan dengan lagu
        </div>
    @endif

    {{-- Isi pesan (font Reenie Beanie) --}}
    <p class="font-reenie text-[22px] leading-[110%] text-[#171717]">{!! \App\Support\EmojiText::small(\Illuminate\Support\Str::limit($msg->message, 90)) !!}</p>

    {{-- Badge penerima --}}
    <div>
        <span class="inline-flex items-center px-3 py-1 rounded-full bg-white/75 border border-[#D9D9D9] text-[11px] text-gray-950">
            <span class="font-semibold">to:</span>&nbsp;{{ $msg->recipient_name }}
        </span>
    </div>
</div>
