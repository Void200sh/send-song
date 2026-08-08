// ── STORY CARD: Unduh pesan jadi gambar PNG 1080x1920 (9:16) untuk Instagram Story ──
// Dipicu tombol [data-story-download], nge-capture elemen [data-story-art]
// yang disembunyikan di luar layar pake html-to-image (render asli browser,
// jadi font Reenie Beanie + warna Tailwind hasilnya sama persis).
import { toPng } from 'html-to-image';

const WIDTH = 1080;
const HEIGHT = 1920;

function triggerDownload(dataUrl) {
    const a = document.createElement('a');
    a.href = dataUrl;
    a.download = 'skanidasong-story.png';
    a.click();
}

// toPng error kalau ada gambar yang gagal di-fetch (cover mati / kena CORS),
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
        return await toPng(art, CAPTURE);
    } catch {
        return await toPng(art, {
            ...CAPTURE,
            filter: (node) => !(node instanceof HTMLImageElement),
        });
    }
}

export function initStoryDownload() {
    const button = document.querySelector('[data-story-download]');
    const art = document.querySelector('[data-story-art]');
    if (!button || !art) return;

    const original = button.textContent.trim();

    button.addEventListener('click', async () => {
        button.disabled = true;
        button.textContent = 'rendering...';
        try {
            // Tunggu font Reenie Beanie selesai dimuat biar ikut ke-embed ke PNG
            await document.fonts.ready;

            const dataUrl = await renderArt(art);

            // Mobile: pakai Web Share API biar bisa langsung share ke Instagram Stories
            if (navigator.canShare && navigator.share) {
                const blob = await (await fetch(dataUrl)).blob();
                const file = new File([blob], 'skanidasong-story.png', { type: 'image/png' });
                if (navigator.canShare({ files: [file] })) {
                    try {
                        await navigator.share({ files: [file], title: 'SkanidaSong story' });
                        return; // berhasil share — gak perlu download
                    } catch (err) {
                        if (err.name === 'AbortError') return; // user batal, jangan download
                        // share gagal (bukan batal) → lanjut ke download biasa
                    }
                }
            }

            triggerDownload(dataUrl);
        } catch (err) {
            console.error('gagal render story:', err);
            alert('gagal membuat gambar. coba lagi.');
        } finally {
            button.disabled = false;
            button.textContent = original;
        }
    });
}
