// ── LIGHTBOX FOTO: preview thumbnail foto kamera tanpa pindah halaman ──
// - Delegasi event di document: [data-photo-open] (button berisi <img> thumbnail)
//   bisa muncul dari render awal maupun hasil infinite scroll — dua-duanya jalan.
// - Klik thumbnail → overlay gelap fullscreen + gambar besar (hampir fullscreen
//   di mobile). Tutup: tombol ✕, klik overlay, atau tombol ESC.
// - Inline styles (pola report.js/feedback.js): Tailwind di project ini hanya
//   memindai *.blade.php, jadi class dinamis JS tidak ter-generate di CSS.

let lightboxEl = null;

function ensureLightbox() {
    if (lightboxEl) return lightboxEl;

    const overlay = document.createElement('div');
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', 'Pratinjau foto');
    overlay.style.cssText =
        'position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;' +
        'padding:20px;background:rgba(17,17,17,.85);opacity:0;transition:opacity .18s ease;';

    const img = document.createElement('img');
    img.alt = 'foto';
    img.style.cssText =
        'max-width:92vw;max-height:88vh;object-fit:contain;border-radius:12px;' +
        'box-shadow:0 24px 60px rgba(0,0,0,.5);background:#fff;transform:scale(.96);' +
        'transition:transform .18s ease;';

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.setAttribute('aria-label', 'Tutup pratinjau');
    closeBtn.textContent = '✕';
    closeBtn.style.cssText =
        'position:absolute;top:16px;right:16px;width:40px;height:40px;border-radius:50%;' +
        'border:1px solid rgba(255,255,255,.25);background:rgba(255,255,255,.12);color:#fff;' +
        'font-size:16px;cursor:pointer;transition:background .15s ease;display:flex;' +
        'align-items:center;justify-content:center;';

    overlay.appendChild(img);
    overlay.appendChild(closeBtn);
    document.body.appendChild(overlay);

    const open = () => {
        document.body.style.overflow = 'hidden';
        overlay.style.display = 'flex';
        requestAnimationFrame(() => {
            overlay.style.opacity = '1';
            img.style.transform = 'scale(1)';
        });
        closeBtn.focus();
    };

    const close = () => {
        overlay.style.opacity = '0';
        img.style.transform = 'scale(.96)';
        document.body.style.overflow = '';
        setTimeout(() => { overlay.style.display = 'none'; }, 180);
    };

    const onKeydown = (e) => {
        // Hanya tutup kalau lightbox sedang terbuka
        if (e.key === 'Escape' && overlay.style.display !== 'none') close();
    };
    document.addEventListener('keydown', onKeydown);

    overlay.addEventListener('click', (e) => {
        if (e.target === overlay || e.target === closeBtn) close();
    });

    overlay._open = (src) => {
        img.src = src;
        open();
    };

    return overlay;
}

document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-photo-open]');
    if (!btn) return;

    // Jangan ikut membuka halaman detail card
    e.preventDefault();
    e.stopPropagation();

    const src = btn.dataset.photoSrc;
    if (!src) return;

    ensureLightbox()._open(src);
});