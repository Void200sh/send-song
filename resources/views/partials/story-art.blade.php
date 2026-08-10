{{-- ─── KARTU STORY 9:16 (1080x1920) — DI-CAPTURE JADI GAMBAR (save as png / share) ─── --}}
{{-- Dipakai di halaman detail (satu) & halaman browse (satu per pesan, buat tombol share card). --}}
{{-- Parameter: $msg — model Message. --}}
{{-- Disembunyikan di luar layar (bukan display:none, biar bisa di-render oleh html-to-image). --}}
<div data-story-art data-story-id="{{ $msg->id }}"
    class="fixed top-0 left-0 theme-{{ $msg->theme ?: 'classic' }} flex items-center justify-center px-24 text-center overflow-hidden opacity-0 pointer-events-none select-none"
    style="width:1080px;height:1920px">
    {{-- Dekorasi tema ikut ter-capture ke PNG (scale 4 biar proporsional di 1080x1920) --}}
    @include('partials.theme-decor', ['theme' => $msg->theme, 'scale' => 4])
    {{-- Wrapper konten — diukur & di-scale otomatis oleh JS biar selalu muat di 1920px --}}
    <div data-story-inner class="w-full flex flex-col items-center justify-center">
        <p class="font-reenie text-[48px] leading-[100%] text-[#171717] mb-14" style="line-height:1.8">SkanidaSong.my.id</p>

        <p class="font-reenie text-[72px] text-[#171717]" style="line-height:1.8">from: {!! \App\Support\EmojiText::small($msg->sender_name ?: 'anonymous') !!}</p>
        <p class="font-reenie text-[72px] text-[#171717] mb-10" style="line-height:1.8">to: {!! \App\Support\EmojiText::small($msg->recipient_name) !!}</p>

        <p class="text-[24px] text-gray-500 mb-12">{{ $msg->kelas }} &bull; {{ $msg->created_at->format('d M Y') }}</p>

        <p class="font-reenie text-[56px] text-[#171717] max-w-full" style="line-height:1.8">{!! \App\Support\EmojiText::small($msg->message) !!}</p>

        @if ($msg->song_title)
            <div class="mt-10 flex items-center gap-6 bg-gray-50 border border-[#E9E9E9] rounded-3xl px-8 py-5">
                @if ($msg->cover_url)
                    <img data-story-cover src="{{ $msg->cover_url }}" class="w-20 h-20 rounded-2xl object-cover" alt="cover">
                @endif
                <div class="text-left">
                    <p class="text-[30px] font-bold text-gray-950 max-w-[560px] truncate" style="line-height:1.4">{{ $msg->song_title }}</p>
                    <p class="text-[24px] text-gray-500" style="line-height:1.4">{{ $msg->song_artist }}</p>
                </div>
            </div>
        @endif
    </div>
</div>
