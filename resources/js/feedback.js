// ─── MODAL SARAN & KRITIK ───
// Muncul otomatis setelah pengunjung berhasil mengirim story (redirect ke
// /messages?feedback=1). Berisi kolom saran, kolom kritik, tombol "tidak"
// (lewati) dan "kirim". Menggunakan inline styles — sama seperti report.js —
// karena Tailwind di project ini hanya memindai *.blade.php, class yang dibuat
// lewat JS dinamis tidak akan ter-generate di CSS production.

const FEEDBACK_STYLE = `
#feedback-modal { position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 16px; background: rgba(17, 17, 17, 0.55); backdrop-filter: blur(4px); animation: fb-fade 0.2s ease-out; }
#feedback-card { width: 100%; max-width: 440px; background: #fff; border-radius: 20px; box-shadow: 0 24px 60px rgba(0,0,0,0.22); padding: 28px 24px 24px; animation: fb-pop 0.22s cubic-bezier(0.2, 0.9, 0.3, 1.2); }
@keyframes fb-fade { from { opacity: 0; } to { opacity: 1; } }
@keyframes fb-pop { from { opacity: 0; transform: translateY(14px) scale(0.97); } to { opacity: 1; transform: translateY(0) scale(1); } }
.fb-emoji { width: 48px; height: 48px; border-radius: 16px; background: #F3F4F6; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 12px; }
#feedback-card h3 { margin: 0 0 6px; font-size: 20px; font-weight: 700; color: #171717; text-align: center; font-family: 'Reenie Beanie', cursive; }
#feedback-card .fb-sub { margin: 0 0 20px; font-size: 13px; color: #6b7280; text-align: center; line-height: 1.5; }
.fb-field label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px; }
.fb-field textarea { width: 100%; padding: 10px 12px; border: 1px solid #D9D9D9; border-radius: 12px; font-size: 14px; font-family: inherit; color: #171717; resize: none; outline: none; transition: border-color 0.15s, box-shadow 0.15s; }
.fb-field textarea:focus { border-color: #171717; box-shadow: 0 0 0 3px rgba(23, 23, 23, 0.08); }
.fb-field textarea::placeholder { color: #9ca3af; }
#feedback-card .fb-buttons { display: flex; gap: 10px; margin-top: 20px; }
#feedback-card .fb-btn { flex: 1; padding: 12px; border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.15s; border: none; }
#feedback-card .fb-no { background: #fff; color: #6b7280; border: 1px solid #D9D9D9; }
#feedback-card .fb-no:hover { color: #171717; border-color: #171717; background: #fafafa; }
#feedback-card .fb-yes { background: #171717; color: #fff; }
#feedback-card .fb-yes:hover { background: #2b2b2b; }
#feedback-card .fb-yes:disabled { opacity: 0.5; cursor: not-allowed; }
#feedback-card .fb-error { margin-top: 12px; font-size: 12.5px; color: #dc2626; text-align: center; display: none; }
#feedback-card .fb-success { margin-top: 12px; font-size: 13px; font-weight: 600; color: #059669; text-align: center; display: none; }
#feedback-toast { position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%); z-index: 10000; background: #171717; color: #fff; font-size: 13.5px; font-weight: 600; padding: 12px 20px; border-radius: 999px; box-shadow: 0 12px 32px rgba(0,0,0,0.28); opacity: 0; pointer-events: none; transition: opacity 0.25s, transform 0.25s; }
#feedback-toast.show { opacity: 1; transform: translateX(-50%) translateY(-4px); }
`;

let feedbackTimer = null;

function toast(msg) {
    let el = document.getElementById('feedback-toast');
    if (!el) {
        el = document.createElement('div');
        el.id = 'feedback-toast';
        document.body.appendChild(el);
    }
    el.textContent = msg;
    el.classList.add('show');
    clearTimeout(feedbackTimer);
    feedbackTimer = setTimeout(() => el.classList.remove('show'), 2600);
}

function closeFeedbackModal() {
    const modal = document.getElementById('feedback-modal');
    if (modal) modal.remove();
    // Bersihkan ?feedback=1 dari URL supaya modal TIDAK muncul lagi
    // saat user pindah halaman pagination / refresh.
    try {
        const search = window.location.search
            .replace(/feedback=1&?/, '')
            .replace(/^\?&/, '?')
            .replace(/&$/, '')
            .replace(/^\?$/, '');
        const clean = window.location.pathname + (search || '');
        window.history.replaceState({}, '', clean);
    } catch (e) {
        /* abaikan */
    }
}

function buildFeedbackModal() {
    const style = document.createElement('style');
    style.textContent = FEEDBACK_STYLE;
    document.head.appendChild(style);

    const modal = document.createElement('div');
    modal.id = 'feedback-modal';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-labelledby', 'feedback-title');

    modal.innerHTML = `
        <div id="feedback-card">
            <div class="fb-emoji">💌</div>
            <h3 id="feedback-title">terima kasih sudah bercerita!</h3>
            <p class="fb-sub">sebelum melanjutkan, ada saran atau kritik untuk kami?<br>masukanmu membantu SkanidaSong jadi lebih baik.</p>
            <div class="fb-field" style="margin-bottom:14px">
                <label for="fb-saran">saran 💡</label>
                <textarea id="fb-saran" rows="2" maxlength="2000" placeholder="tulis sarannya di sini..."></textarea>
            </div>
            <div class="fb-field">
                <label for="fb-kritik">kritik ✍️</label>
                <textarea id="fb-kritik" rows="2" maxlength="2000" placeholder="tulis kritiknya di sini..."></textarea>
            </div>
            <p class="fb-error">isi minimal satu kolom ya.</p>
            <p class="fb-success">✓ terima kasih — masukanmu terkirim!</p>
            <div class="fb-buttons">
                <button type="button" class="fb-btn fb-no" data-fb-no>tidak</button>
                <button type="button" class="fb-btn fb-yes" data-fb-yes>kirim</button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    const card = modal.querySelector('#feedback-card');
    const saran = modal.querySelector('#fb-saran');
    const kritik = modal.querySelector('#fb-kritik');
    const err = modal.querySelector('.fb-error');
    const ok = modal.querySelector('.fb-success');
    const yesBtn = modal.querySelector('[data-fb-yes]');

    // Fokus ke kolom saran (bukan tombol bahaya) supaya langsung bisa mengetik
    setTimeout(() => saran.focus(), 60);

    function resetFeedback() {
        err.style.display = 'none';
        ok.style.display = 'none';
        yesBtn.disabled = false;
        yesBtn.textContent = 'kirim';
    }

    modal.querySelector('[data-fb-no]').addEventListener('click', () => {
        closeFeedbackModal();
        toast('oke, sampai jumpa! 👋');
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeFeedbackModal();
    });

    const onKey = function (e) {
        if (e.key === 'Escape' && document.getElementById('feedback-modal')) {
            closeFeedbackModal();
            document.removeEventListener('keydown', onKey);
        }
    };
    document.addEventListener('keydown', onKey);

    yesBtn.addEventListener('click', async () => {
        const payload = {
            saran: saran.value.trim(),
            kritik: kritik.value.trim(),
        };
        if (!payload.saran && !payload.kritik) {
            err.style.display = 'block';
            return;
        }
        resetFeedback();
        yesBtn.disabled = true;
        yesBtn.textContent = 'mengirim...';

        try {
            const res = await fetch('/feedback', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (!res.ok || !data.ok) {
                throw new Error(data.message || 'Gagal mengirim.');
            }
            ok.style.display = 'block';
            setTimeout(() => {
                closeFeedbackModal();
                toast('✓ terima kasih — masukanmu terkirim!');
            }, 700);
        } catch (e) {
            err.textContent = 'gagal mengirim — coba lagi ya.';
            err.style.display = 'block';
            resetFeedback();
        }
    });
}

export function initFeedbackModal() {
    // Muncul otomatis hanya ketika halaman browse dibuka lewat ?feedback=1
    // (hasil redirect setelah story berhasil dikirim).
    if (new URLSearchParams(window.location.search).get('feedback') === '1') {
        buildFeedbackModal();
    }
}
