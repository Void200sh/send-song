// ── KAMERA (halaman story): jepret foto langsung dari kamera, TANPA upload file ──
// - "buka kamera" → getUserMedia (kamera belakang kalau ada) → preview <video>.
// - "jepret" → capture ke canvas (max 900px, JPEG q0.72) → data URL → hidden input #cam-photo.
// - Foto OPSIONAL: kalau kamera tidak ada / izin ditolak, tetap bisa kirim tanpa foto.
// - Stream dimatikan setelah jepret, saat submit, dan saat halaman ditinggalkan.

const MAX_EDGE = 900;   // sisi terpanjang canvas (biar base64 kecil, aman di post_max_size)
const JPEG_QUALITY = 0.72;

let stream = null;

function stopStream() {
    if (!stream) return;
    stream.getTracks().forEach((t) => t.stop());
    stream = null;
}

export function initCamera() {
    const wrap = document.querySelector('[data-camera]');
    if (!wrap) return;

    const video = document.getElementById('cam-video');
    const hidden = document.getElementById('cam-photo');
    const live = wrap.querySelector('[data-cam-live]');
    const shot = wrap.querySelector('[data-cam-shot]');
    const shotImg = document.getElementById('cam-shot-img');
    const btnOpen = wrap.querySelector('[data-cam-open]');
    const btnSnap = wrap.querySelector('[data-cam-snap]');
    const btnCancel = wrap.querySelector('[data-cam-cancel]');
    const btnRetake = wrap.querySelector('[data-cam-retake]');
    const btnClear = wrap.querySelector('[data-cam-clear]');
    const status = wrap.querySelector('[data-cam-status]');

    // Semua elemen yang butuh JS mulai hidden; tanpa JS tidak ada yang muncul.
    btnOpen.classList.remove('hidden');

    const setStatus = (text) => { status.textContent = text || ''; };

    function showLive() {
        live.classList.remove('hidden');
        shot.classList.add('hidden');
        btnOpen.classList.add('hidden');
        btnSnap.classList.remove('hidden');
        btnCancel.classList.remove('hidden');
        btnRetake.classList.add('hidden');
        btnClear.classList.add('hidden');
    }

    function showShot() {
        stopStream();
        live.classList.add('hidden');
        shot.classList.remove('hidden');
        btnOpen.classList.add('hidden');
        btnSnap.classList.add('hidden');
        btnCancel.classList.add('hidden');
        btnRetake.classList.remove('hidden');
        btnClear.classList.remove('hidden');
    }

    function resetIdle() {
        stopStream();
        video.srcObject = null;
        live.classList.add('hidden');
        shot.classList.add('hidden');
        btnOpen.classList.remove('hidden');
        btnSnap.classList.add('hidden');
        btnCancel.classList.add('hidden');
        btnRetake.classList.add('hidden');
        btnClear.classList.add('hidden');
    }

    // Batalkan kamera: tutup preview tanpa mengambil foto, kembali ke awal.
    function cancelCamera() {
        hidden.value = '';
        shotImg.removeAttribute('src');
        resetIdle();
        setStatus('');
    }

    // Kamera murni (getUserMedia) — tidak ada opsi pilih file/gallery sama sekali.
    async function openCamera() {
        setStatus('');
        if (!navigator.mediaDevices?.getUserMedia) {
            setStatus('kamera tidak didukung browser ini — foto opsional, lanjut tanpa foto.');
            return;
        }
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                audio: false,
                video: {
                    facingMode: 'environment',
                    width: { ideal: 1280 },
                    height: { ideal: 960 },
                },
            });
            video.srcObject = stream;
            await video.play();
            showLive();
        } catch (err) {
            stopStream();
            if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                // Bisa karena: user menolak prompt, izin tersimpan "block", halaman
                // bukan HTTPS, ATAU hosting mengirim header permissions-policy yang
                // memblokir kamera (camera=()) — kasus umum di shared hosting.
                setStatus('izin kamera ditolak — cek izin kamera browser & pastikan situs diakses via https. foto opsional, lanjut tanpa foto.');
            } else if (err.name === 'NotFoundError' || err.name === 'OverconstrainedError') {
                setStatus('kamera tidak ditemukan — foto opsional, lanjut tanpa foto.');
            } else {
                setStatus('gagal membuka kamera — foto opsional, lanjut tanpa foto.');
            }
        }
    }

    function snap() {
        if (!stream || !video.videoWidth) return;

        // Skala ke MAX_EDGE di sisi terpanjang, pertahankan rasio aspek.
        const scale = Math.min(1, MAX_EDGE / Math.max(video.videoWidth, video.videoHeight));
        const w = Math.round(video.videoWidth * scale);
        const h = Math.round(video.videoHeight * scale);

        const canvas = document.createElement('canvas');
        canvas.width = w;
        canvas.height = h;
        canvas.getContext('2d').drawImage(video, 0, 0, w, h);

        hidden.value = canvas.toDataURL('image/jpeg', JPEG_QUALITY);
        shotImg.src = hidden.value;
        showShot();
        setStatus('');
    }

    btnOpen.addEventListener('click', openCamera);
    btnSnap.addEventListener('click', snap);
    btnCancel.addEventListener('click', cancelCamera);
    btnRetake.addEventListener('click', () => {
        hidden.value = '';
        shotImg.removeAttribute('src');
        openCamera();
    });
    btnClear.addEventListener('click', cancelCamera);

    // Matikan stream saat form disubmit & halaman ditinggalkan (hemat baterai/mic).
    const form = document.getElementById('story-form');
    form?.addEventListener('submit', stopStream, { once: true });
    window.addEventListener('pagehide', stopStream);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') stopStream();
    });
}
