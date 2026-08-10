// ── SHARE: bagikan pesan berupa LINK (tanpa gambar) ──
// - Card di halaman browse → bagikan link pesan.
// - Halaman detail → bagikan link pesan.
// Prioritas:
//   1. Web Share API (teks pendek + link).
//   2. Salin link ke clipboard (+ toast "link disalin ✓").
// Tombol "save as png" tetap tersedia untuk mengunduh gambarnya.

// ── TOAST kecil (feedback "link disalin") ──
let toastEl = null;
function toast(message) {
    if (!toastEl) {
        toastEl = document.createElement('div');
        toastEl.id = 'share-toast';
        toastEl.style.cssText =
            'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#171717;' +
            'color:#fff;padding:10px 18px;border-radius:999px;font-size:13px;font-family:inherit;' +
            'z-index:9999;opacity:0;transition:opacity .2s ease;pointer-events:none;max-width:90vw;' +
            'text-align:center;box-shadow:0 4px 16px rgba(0,0,0,.18);';
        document.body.appendChild(toastEl);
    }
    toastEl.textContent = message;
    toastEl.style.opacity = '1';
    clearTimeout(toastEl._timer);
    toastEl._timer = setTimeout(() => { toastEl.style.opacity = '0'; }, 2400);
}

// Teks share diambil dari data-attribute tombol (di-set di Blade).
function buildShareText(btn) {
    const url = btn.dataset.shareUrl || window.location.href;
    const recipient = btn.dataset.shareRecipient || '';
    return {
        url,
        title: `SkanidaSong — pesan untuk ${recipient}`.trim(),
        text: `Ada pesan buat ${recipient} di SkanidaSong 💌\n${url}`.trim(),
    };
}

async function copyLink(url) {
    try {
        await navigator.clipboard.writeText(url);
        toast('link disalin ✓');
    } catch {
        // Clipboard API ditolak → kasih prompt manual
        window.prompt('Salin link ini:', url);
    }
}

// Inti share: Web Share API dengan teks + link; kalau gagal/dibatal user,
// turun ke salin link. AbortError = user menutup share sheet → diam saja.
export async function shareMessage({ title, text, url }) {
    if (!navigator.share) {
        await copyLink(url);
        return;
    }

    try {
        await navigator.share({ title, text, url });
    } catch (err) {
        if (err.name === 'AbortError') return;
        await copyLink(url);
    }
}

// ── TOMBOL SHARE DI SETIAP CARD (halaman browse) ──
export function initCardShare() {
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-share-card]');
        if (!btn) return;

        // Jangan sampai klik tombol ini ikut membuka halaman detail card
        e.preventDefault();
        e.stopPropagation();

        const { url, title, text } = buildShareText(btn);
        await shareMessage({ title, text, url });
    });
}

// ── TOMBOL SHARE DI HALAMAN DETAIL ──
export function initDetailShare() {
    const btn = document.querySelector('[data-story-share]');
    if (!btn) return;

    btn.addEventListener('click', async () => {
        const { url, title, text } = buildShareText(btn);
        await shareMessage({ title, text, url });
    });
}
