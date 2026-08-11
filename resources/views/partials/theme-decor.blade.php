{{-- ─── DEKORASI TEMA KARTU (emoji melayang ala chat TikTok) ─── --}}
{{-- Parameter: --}}
{{--   $theme — key tema (bunga/senja/laut/lavender/mint/neon/film/pastel). 'classic' / null → tidak render apa-apa --}}
{{--   $scale — pengali ukuran emoji (dipakai di kartu story 1080x1920, kirim 4) --}}
@php
    $scale = $scale ?? 1;
    $decor = match ($theme ?? null) {
        'bunga' => [
            ['🌸', '8%', '6%', 34], ['🌷', '76%', '10%', 28], ['🌺', '14%', '68%', 32],
            ['🌻', '84%', '64%', 26], ['🌸', '52%', '82%', 22], ['🌼', '32%', '18%', 20],
        ],
        'senja' => [
            ['☀️', '80%', '6%', 40], ['🌅', '10%', '62%', 30], ['🌤️', '62%', '72%', 24],
            ['✨', '24%', '12%', 20],
        ],
        'laut' => [
            ['🌊', '6%', '70%', 30], ['🐚', '80%', '14%', 26], ['🫧', '22%', '12%', 22],
            ['🐠', '70%', '60%', 26], ['🫧', '48%', '24%', 18],
        ],
        'lavender' => [
            ['💜', '10%', '8%', 28], ['✨', '82%', '14%', 22], ['🌸', '18%', '70%', 26],
            ['💫', '66%', '76%', 24],
        ],
        'mint' => [
            ['🍃', '82%', '8%', 30], ['🌱', '12%', '66%', 26], ['💚', '58%', '78%', 24],
            ['🌿', '30%', '14%', 22],
        ],
        'neon' => [
            ['💜', '80%', '6%', 32], ['✨', '10%', '10%', 26], ['⚡', '84%', '66%', 28],
            ['🌟', '18%', '70%', 24], ['💫', '52%', '84%', 22],
        ],
        'film' => [
            ['🎬', '82%', '6%', 30], ['🎞️', '10%', '12%', 26], ['🎥', '82%', '68%', 26],
            ['🍿', '16%', '70%', 26], ['📽️', '50%', '84%', 22],
        ],
        'pastel' => [
            ['🍭', '84%', '6%', 30], ['🧁', '10%', '12%', 26], ['🎈', '82%', '66%', 26],
            ['🍬', '16%', '70%', 24], ['✨', '50%', '84%', 20],
        ],
        default => [],
    };
@endphp

@if ($decor)
    <div class="pointer-events-none absolute inset-0 overflow-hidden select-none" aria-hidden="true">
        {{-- Rotasi emoji DETERMINISTIK (seed tema + indeks) biar output stabil tiap render --}}
        {{-- (random_int() bikin HTML beda tiap load → merusak caching & dekorasi suka "berkedip") --}}
        @foreach ($decor as $i => [$emoji, $left, $top, $size])
            @php $rot = (crc32($theme . $i) % 33) - 16; @endphp
            <span class="absolute" style="left:{{ $left }};top:{{ $top }};font-size:{{ $size * $scale }}px;opacity:0.22;line-height:1;transform:rotate({{ $rot }}deg);">{{ $emoji }}</span>
        @endforeach
    </div>
@endif
