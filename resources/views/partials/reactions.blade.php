{{-- ─── REAKSI EMOJI (ala WhatsApp) ─── --}}
{{-- Butuh variabel: $msg (Message) dan $mine (array emoji yang sudah direaksikan pengunjung ini). --}}
{{-- Chip reaksi yang SUDAH ADA tampil langsung (emoji + jumlah); tombol "+" membuka picker emoji. --}}
{{-- Toggle dikerjakan lewat JS (resources/js/reactions.js) + POST /messages/{id}/react. --}}
@php $counts = $msg->reactionCounts(); @endphp
<div class="mt-3 pt-3 border-t border-black/10 flex flex-wrap items-center gap-1.5" data-reactions data-message-id="{{ $msg->id }}">
    {{-- Chip reaksi yang sudah ada — disembunyikan kalau count-nya 0 --}}
    @foreach (\App\Models\Message::REACTION_EMOJIS as $emoji)
        @php
            $count = $counts[$emoji] ?? 0;
            $active = in_array($emoji, $mine, true);
        @endphp
        <button type="button" data-react data-emoji="{{ $emoji }}" data-active="{{ $active ? '1' : '0' }}"
            aria-pressed="{{ $active ? 'true' : 'false' }}"
            title="{{ $active ? 'Batalkan reaksi' : 'Beri reaksi' }}"
            class="{{ $count === 0 ? 'hidden' : '' }} flex items-center gap-1 px-2.5 py-1 rounded-full border transition-all cursor-pointer select-none
                {{ $active
                    ? 'bg-[#171717] border-[#171717] text-white shadow-sm'
                    : 'bg-white/70 border-[#E9E9E9] text-gray-600 hover:border-[#171717] hover:text-gray-900' }}">
            <span class="text-base leading-none">{{ $emoji }}</span>
            <span data-react-count class="text-xs font-semibold tabular-nums">{{ $count }}</span>
        </button>
    @endforeach

    {{-- Tombol "+" — buka picker emoji (ala WhatsApp) --}}
    <div class="relative" data-react-picker>
        <button type="button" data-react-open aria-label="Tambah reaksi" aria-expanded="false"
            aria-haspopup="menu" title="Tambah reaksi"
            class="w-8 h-8 flex items-center justify-center rounded-full border border-[#E9E9E9] bg-white/70 text-gray-500 hover:text-gray-900 hover:border-[#171717] transition-all cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 5v14M5 12h14" />
            </svg>
        </button>
        {{-- Picker emoji — posisi dikontrol JS (fixed) biar gak ke-clip oleh overflow-hidden kartu --}}
        <div data-react-popover role="menu" class="hidden z-40 flex items-center gap-1 rounded-full border border-[#E9E9E9] bg-white p-1.5 shadow-xl">
            @foreach (\App\Models\Message::REACTION_EMOJIS as $emoji)
                <button type="button" data-react-option data-emoji="{{ $emoji }}"
                    aria-label="Reaksi {{ $emoji }}" title="{{ $emoji }}"
                    class="w-9 h-9 flex items-center justify-center text-[22px] rounded-full hover:bg-gray-100 hover:scale-110 transition-all cursor-pointer">
                    {{ $emoji }}
                </button>
            @endforeach
        </div>
    </div>
</div>
