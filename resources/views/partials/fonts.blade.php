{{-- ─── FONT LOKAL (same-origin) ─── --}}
{{-- Font dimuat dari /fonts (public/fonts) BUKAN CDN fonts.bunny.net. --}}
{{-- Penting untuk fitur "save as png": html-to-image hanya bisa meng-embed font --}}
{{-- dari stylesheet yang bisa dibaca (same-origin). CDN cross-origin (CORS) gagal --}}
{{-- dibaca → gambar PNG jatuh ke font fallback cursive yang metriknya beda --}}
{{-- → tulisan jadi tumpuk/berantakan di gambar. --}}
<style>
    @font-face {
        font-family: 'Reenie Beanie';
        font-style: normal;
        font-weight: 400;
        font-display: swap;
        src: url('/fonts/reenie-beanie-400.woff2') format('woff2');
    }
    @font-face {
        font-family: 'Plus Jakarta Sans';
        font-style: normal;
        font-weight: 400;
        font-display: swap;
        src: url('/fonts/plus-jakarta-sans-400.woff2') format('woff2');
    }
    @font-face {
        font-family: 'Plus Jakarta Sans';
        font-style: normal;
        font-weight: 500;
        font-display: swap;
        src: url('/fonts/plus-jakarta-sans-500.woff2') format('woff2');
    }
    @font-face {
        font-family: 'Plus Jakarta Sans';
        font-style: normal;
        font-weight: 600;
        font-display: swap;
        src: url('/fonts/plus-jakarta-sans-600.woff2') format('woff2');
    }
    @font-face {
        font-family: 'Plus Jakarta Sans';
        font-style: normal;
        font-weight: 700;
        font-display: swap;
        src: url('/fonts/plus-jakarta-sans-700.woff2') format('woff2');
    }
</style>
