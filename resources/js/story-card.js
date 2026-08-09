// ── STORY CARD: Unduh pesan jadi gambar PNG 1080x1920 (9:16) untuk Instagram Story ──
// Dipicu tombol [data-story-download], nge-capture elemen [data-story-art]
// yang disembunyikan di luar layar pake html-to-image (render asli browser,
// jadi font Reenie Beanie + warna Tailwind hasilnya sama persis).
// Klik = SELALU download langsung (tanpa share sheet), di semua perangkat.
import { toBlob } from 'html-to-image';

const WIDTH = 1080;
const HEIGHT = 1920;

// Nama file unik: ID pesan + timestamp, biar gak ketimpa file unduhan sebelumnya
function buildFilename(art) {
    const id = art?.dataset.storyId || 'x';
    const t = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    const stamp =
        t.getFullYear() +
        pad(t.getMonth() + 1) +
        pad(t.getDate()) + '-' +
        pad(t.getHours()) +
        pad(t.getMinutes()) +
        pad(t.getSeconds());
    return `skanidasong-story-${id}-${stamp}.png`;
}

// Download via Blob URL — lebih reliable di semua browser (termasuk iOS Safari)
// daripada data URL besar. URL di-revoke setelah klik supaya gak bocor memory.
function triggerDownload(blob, filename) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    setTimeout(() => URL.revokeObjectURL(url), 5000);
}

// toBlob error kalau ada gambar yang gagal di-fetch (cover mati / kena CORS),
// jadi render dua tahap: dengan gambar dulu, gagal → ulang tanpa gambar.
// backgroundColor bikin kanvas pasti putih solid (bukan transparan/hitam),
// dan style override memaksa klon jadi opacity 1 + posisi di pojok (0,0)
// karena elemen aslinya disembunyikan pake opacity-0.
const CAPTURE = {
    width: WIDTH,
    height: HEIGHT,
    pixelRatio: 1,
    backgroundColor: '#ffffff',
    style: {
        position: 'fixed',
        left: '0px',
        top: '0px',
        opacity: '1',
    },
};

async function renderArt(art) {
    try {
        return await toBlob(art, CAPTURE);
    } catch {
        return await toBlob(art, {
            ...CAPTURE,
            filter: (node) => !(node instanceof HTMLImageElement),
        });
    }
}

// Tunggu font selesai dimuat, tapi jangan selamanya — kalo lambat (misal CDN
// font bermasalah), tetap lanjut render biar tombol gak menggantung.
function fontsReady() {
    if (!document.fonts?.ready) return Promise.resolve();
    const timeout = new Promise((resolve) => setTimeout(resolve, 3000));
    return Promise.race([document.fonts.ready, timeout]);
}

export function initStoryDownload() {
    const button = document.querySelector('[data-story-download]');
    const art = document.querySelector('[data-story-art]');
    const errorBox = document.querySelector('[data-story-error]');
    if (!button || !art) return;

    const original = button.textContent.trim();

    const showError = (message) => {
        if (!errorBox) return;
        errorBox.textContent = message;
        errorBox.classList.remove('hidden');
    };

    const clearError = () => {
        if (!errorBox) return;
        errorBox.classList.add('hidden');
        errorBox.textContent = '';
    };

    button.addEventListener('click', async () => {
        button.disabled = true;
        button.textContent = 'rendering...';
        clearError();
        try {
            await fontsReady();

            const blob = await renderArt(art);
            if (!blob) throw new Error('blob kosong');
            triggerDownload(blob, buildFilename(art));
        } catch (err) {
            console.error('gagal render story:', err);
            showError('gagal membuat gambar. coba lagi.');
        } finally {
            button.disabled = false;
            button.textContent = original;
        }
    });
}
