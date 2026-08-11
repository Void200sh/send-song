// ── LAPOR: laporkan pesan yang tidak pantas ke admin ──
// - Tombol [data-report] ada di card browse & halaman detail.
// - Klik → modal 2 langkah:
//      Langkah 1: pilih alasan laporan (spam / konten kasar·ujaran kebencian /
//                 bullying / kekerasan / informasi palsu / lainnya).
//                 Kalau pilih "lainnya" → muncul kolom teks alasan bebas.
//      Langkah 2: ringkasan pilihan → tombol "ya, laporkan".
// - POST /messages/{id}/report (JSON) dengan field reason → toast sukses.
// - Klik aman: tidak ikut membuka halaman detail (card dibungkus <a>).
import { toast } from './share.js';

// ── DAFTAR ALASAN LAPORAN ──
const REASONS = [
    'spam',
    'konten kasar / ujaran kebencian',
    'bullying',
    'kekerasan',
    'informasi palsu',
    'lainnya',
];

// ── MODAL LAPORAN (custom, 2 langkah) ──
// Dibangun dengan inline styles (Tailwind tidak memindai .js, jadi class
// dinamis tidak ikut ter-generate di CSS production).
let reportModal = null;

function ensureModal() {
    if (reportModal) return reportModal;

    reportModal = document.createElement('div');
    reportModal.style.cssText =
        'position:fixed;inset:0;z-index:9998;display:flex;align-items:center;justify-content:center;' +
        'padding:20px;background:rgba(23,23,23,.45);opacity:0;transition:opacity .18s ease;';

    const card = document.createElement('div');
    card.setAttribute('role', 'alertdialog');
    card.setAttribute('aria-modal', 'true');
    card.setAttribute('aria-labelledby', 'report-modal-title');
    card.style.cssText =
        'width:100%;max-width:380px;background:#fff;border:1px solid #E9E9E9;border-radius:20px;' +
        'padding:24px;text-align:center;box-shadow:0 20px 50px rgba(0,0,0,.18);' +
        'transform:scale(.95);transition:transform .18s ease;font-family:inherit;max-height:90vh;' +
        'overflow-y:auto;';

    // Langkah 1: pilih alasan
    const step1 = document.createElement('div');
    step1.innerHTML = `
        <div style="width:52px;height:52px;margin:0 auto;border-radius:50%;background:#FEF2F2;
            border:1px solid #FECACA;display:flex;align-items:center;justify-content:center;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#DC2626"
                stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 9v4m0 4h.01M10.3 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.7 3.86a2 2 0 0 0-3.4 0z"/>
            </svg>
        </div>
        <h3 id="report-modal-title" style="margin:14px 0 4px;font-size:18px;font-weight:700;color:#171717;">Laporkan pesan ini?</h3>
        <p style="margin:0 0 16px;font-size:13px;line-height:1.5;color:#6B7280;">Pilih alasan kamu melaporkan pesan ini.</p>
        <div data-report-reasons style="display:grid;gap:8px;text-align:left;"></div>
        <div data-report-other style="display:none;margin-top:10px;">
            <textarea data-report-other-text rows="2" maxlength="200" placeholder="Tulis alasanmu di sini..."
                style="width:100%;padding:10px 12px;border:1px solid #D9D9D9;border-radius:12px;font-size:13px;
                color:#171717;background:#fff;resize:none;outline:none;box-sizing:border-box;
                transition:border-color .15s ease;"></textarea>
        </div>
        <div style="display:flex;gap:10px;margin-top:18px;">
            <button type="button" data-report-cancel
                style="flex:1;padding:11px 0;border-radius:12px;border:1px solid #D9D9D9;background:#fff;
                    color:#4B5563;font-size:14px;font-weight:600;cursor:pointer;
                    transition:border-color .15s ease,color .15s ease;">
                batal
            </button>
            <button type="button" data-report-next
                style="flex:1;padding:11px 0;border-radius:12px;border:1px solid #171717;background:#171717;
                    color:#fff;font-size:14px;font-weight:600;cursor:pointer;opacity:.4;
                    transition:background .15s ease,opacity .15s ease;" disabled>
                next
            </button>
        </div>
    `;

    // Langkah 2: ringkasan + konfirmasi
    const step2 = document.createElement('div');
    step2.style.display = 'none';
    step2.innerHTML = `
        <div style="width:52px;height:52px;margin:0 auto;border-radius:50%;background:#FEF2F2;
            border:1px solid #FECACA;display:flex;align-items:center;justify-content:center;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#DC2626"
                stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 9v4m0 4h.01M10.3 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.7 3.86a2 2 0 0 0-3.4 0z"/>
            </svg>
        </div>
        <h3 style="margin:14px 0 4px;font-size:18px;font-weight:700;color:#171717;">Konfirmasi laporan</h3>
        <p style="margin:0 0 14px;font-size:13px;line-height:1.5;color:#6B7280;">Kamu melaporkan pesan ini karena:</p>
        <div data-report-summary
            style="display:inline-block;padding:8px 16px;border-radius:999px;background:#FEF2F2;border:1px solid #FECACA;
                color:#B91C1C;font-size:13px;font-weight:600;max-width:100%;overflow-wrap:break-word;"></div>
        <div style="display:flex;gap:10px;margin-top:18px;">
            <button type="button" data-report-back
                style="flex:1;padding:11px 0;border-radius:12px;border:1px solid #D9D9D9;background:#fff;
                    color:#4B5563;font-size:14px;font-weight:600;cursor:pointer;
                    transition:border-color .15s ease,color .15s ease;">
                kembali
            </button>
            <button type="button" data-report-confirm
                style="flex:1;padding:11px 0;border-radius:12px;border:1px solid #DC2626;background:#DC2626;
                    color:#fff;font-size:14px;font-weight:600;cursor:pointer;
                    transition:background .15s ease;">
                ya, laporkan
            </button>
        </div>
    `;

    card.appendChild(step1);
    card.appendChild(step2);
    reportModal.appendChild(card);
    document.body.appendChild(reportModal);

    // Render opsi alasan sebagai tombol radio
    const reasonsBox = card.querySelector('[data-report-reasons]');
    REASONS.forEach((reason) => {
        const label = document.createElement('label');
        label.style.cssText =
            'display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid #E9E9E9;' +
            'border-radius:12px;cursor:pointer;transition:border-color .15s ease,background .15s ease;' +
            'background:#fff;';
        label.innerHTML = `
            <input type="radio" name="report-reason" value="${reason}"
                style="width:16px;height:16px;accent-color:#171717;cursor:pointer;flex-shrink:0;">
            <span style="font-size:13.5px;color:#374151;font-weight:500;">${reason}</span>
        `;
        reasonsBox.appendChild(label);
    });

    // Hover state tombol (micro-interaction)
    const addHover = (el, on, off) => {
        el.addEventListener('mouseenter', () => { el.style.background = on; });
        el.addEventListener('mouseleave', () => { el.style.background = off; });
    };
    addHover(card.querySelector('[data-report-cancel]'), '#f9fafb', '#fff');
    addHover(card.querySelector('[data-report-back]'), '#f9fafb', '#fff');
    addHover(card.querySelector('[data-report-confirm]'), '#B91C1C', '#DC2626');
    addHover(card.querySelector('[data-report-next]'), '#374151', '#171717');

    return reportModal;
}

// Tampilkan modal, balas Promise<{ ok: boolean, reason?: string }>.
function askReport() {
    const modal = ensureModal();
    const card = modal.firstElementChild;
    const step1 = card.children[0];
    const step2 = card.children[1];
    const reasonsBox = card.querySelector('[data-report-reasons]');
    const otherWrap = card.querySelector('[data-report-other]');
    const otherText = card.querySelector('[data-report-other-text]');
    const nextBtn = card.querySelector('[data-report-next]');
    const cancelBtn = card.querySelector('[data-report-cancel]');
    const backBtn = card.querySelector('[data-report-back]');
    const confirmBtn = card.querySelector('[data-report-confirm]');
    const summaryEl = card.querySelector('[data-report-summary]');
    const radios = reasonsBox.querySelectorAll('input[type="radio"]');
    const previouslyFocused = document.activeElement;

    let selectedReason = null;
    let settled = false;

    return new Promise((resolve) => {
        const close = (result) => {
            if (settled) return;
            settled = true;
            modal.style.opacity = '0';
            card.style.transform = 'scale(.95)';
            cleanup();
            setTimeout(() => {
                modal.style.display = 'none';
                previouslyFocused?.focus?.();
            }, 180);
            resolve(result);
        };

        const onKeydown = (e) => {
            if (e.key === 'Escape') {
                e.preventDefault();
                close({ ok: false });
            }
        };

        const onOverlayClick = (e) => {
            if (e.target === modal) close({ ok: false });
        };

        const onReasonChange = () => {
            const checked = [...radios].find((r) => r.checked);
            selectedReason = checked ? checked.value : null;

            // "lainnya" → tampilkan kolom teks bebas
            const showOther = selectedReason === 'lainnya';
            otherWrap.style.display = showOther ? 'block' : 'none';
            if (!showOther) otherText.value = '';

            // Next aktif kalau alasan terpilih (dan untuk "lainnya", teksnya juga terisi)
            const valid = selectedReason !== null && (!showOther || otherText.value.trim() !== '');
            nextBtn.disabled = !valid;
            nextBtn.style.opacity = valid ? '1' : '.4';

            // Highlight opsi yang dipilih
            reasonsBox.querySelectorAll('label').forEach((label) => {
                const input = label.querySelector('input');
                const on = input.checked;
                label.style.borderColor = on ? '#171717' : '#E9E9E9';
                label.style.background = on ? '#f9fafb' : '#fff';
            });
        };

        const onOtherInput = () => {
            const valid = otherText.value.trim() !== '';
            nextBtn.disabled = !valid;
            nextBtn.style.opacity = valid ? '1' : '.4';
        };

        const onNext = () => {
            if (nextBtn.disabled) return;
            const reason = selectedReason === 'lainnya'
                ? otherText.value.trim()
                : selectedReason;
            summaryEl.textContent = reason;
            step1.style.display = 'none';
            step2.style.display = 'block';
            confirmBtn.focus();
        };

        const onBack = () => {
            step2.style.display = 'none';
            step1.style.display = 'block';
            nextBtn.focus();
        };

        const onCancel = () => close({ ok: false });
        const onConfirm = () => {
            const reason = selectedReason === 'lainnya'
                ? otherText.value.trim()
                : selectedReason;
            close({ ok: true, reason: reason || undefined });
        };

        const cleanup = () => {
            document.removeEventListener('keydown', onKeydown, true);
            modal.removeEventListener('click', onOverlayClick);
            radios.forEach((r) => r.removeEventListener('change', onReasonChange));
            otherText.removeEventListener('input', onOtherInput);
            nextBtn.removeEventListener('click', onNext);
            backBtn.removeEventListener('click', onBack);
            cancelBtn.removeEventListener('click', onCancel);
            confirmBtn.removeEventListener('click', onConfirm);
        };

        document.addEventListener('keydown', onKeydown, true);
        modal.addEventListener('click', onOverlayClick);
        radios.forEach((r) => r.addEventListener('change', onReasonChange));
        otherText.addEventListener('input', onOtherInput);
        nextBtn.addEventListener('click', onNext);
        backBtn.addEventListener('click', onBack);
        cancelBtn.addEventListener('click', onCancel);
        confirmBtn.addEventListener('click', onConfirm);

        // Reset state tiap kali dibuka
        radios.forEach((r) => { r.checked = false; });
        otherText.value = '';
        otherWrap.style.display = 'none';
        reasonsBox.querySelectorAll('label').forEach((label) => {
            label.style.borderColor = '#E9E9E9';
            label.style.background = '#fff';
        });
        selectedReason = null;
        nextBtn.disabled = true;
        nextBtn.style.opacity = '.4';
        step2.style.display = 'none';
        step1.style.display = 'block';

        modal.style.display = 'flex';
        requestAnimationFrame(() => {
            modal.style.opacity = '1';
            card.style.transform = 'scale(1)';
        });
        cancelBtn.focus();
    });
}

document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-report]');
    if (!btn) return;

    // Jangan sampai klik tombol ini ikut membuka halaman detail card
    e.preventDefault();
    e.stopPropagation();

    const messageId = btn.dataset.reportId;
    if (!messageId) return;

    const result = await askReport();
    if (!result.ok) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    btn.disabled = true;
    try {
        const res = await fetch(`/messages/${messageId}/report`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ reason: result.reason || '' }),
        });
        if (!res.ok) throw new Error('request gagal');
        toast('terima kasih — laporan terkirim ✓');
    } catch (err) {
        toast('gagal mengirim laporan. coba lagi.');
    } finally {
        btn.disabled = false;
    }
});
