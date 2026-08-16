// ── KAMERA (halaman story): jepret foto langsung dari kamera, TANPA upload file ──
// - "buka kamera" → getUserMedia (kamera belakang kalau ada) → preview <video>.
// - "jepret" → capture ke canvas (max 900px, JPEG q0.72) → data URL → hidden input #cam-photo.
// - Foto OPSIONAL: kalau kamera tidak ada / izin ditolak, tetap bisa kirim tanpa foto.
// - Stream dimatikan setelah jepret, saat submit, dan saat halaman ditinggalkan.

const MAX_EDGE = 900;   // sisi terpanjang canvas (biar base64 kecil, aman di post_max_size)
const JPEG_QUALITY = 0.72;

let stream = null;
let facingMode = 'environment';   // kamera belakang default; 'user' = kamera depan
let canFlip = false;              // tombol balik kamera hanya relevan kalau ada >1 kamera
let mirrored = false;             // preview & hasil jepret dipantulkan horizontal (kiri↔kanan)

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
    const btnFlip = wrap.querySelector('[data-cam-flip]');
    const btnMirror = wrap.querySelector('[data-cam-mirror]');
    const status = wrap.querySelector('[data-cam-status]');

    // Semua elemen yang butuh JS mulai hidden; tanpa JS tidak ada yang muncul.
    btnOpen.classList.remove('hidden');

    const setStatus = (text) => { status.textContent = text || ''; };

    // Tombol balik kamera hanya berguna kalau perangkat punya >1 kamera (HP).
    // Enumerate berjalan tanpa izin; di desktop (1 webcam) tombol disembunyikan.
    if (navigator.mediaDevices?.enumerateDevices) {
        navigator.mediaDevices.enumerateDevices()
            .then((devices) => {
                canFlip = devices.filter((d) => d.kind === 'videoinput').length >= 2;
                if (!live.classList.contains('hidden')) {
                    btnFlip.classList.toggle('hidden', !canFlip);
                }
            })
            .catch(() => { canFlip = false; });
    }

    function showLive() {
        live.classList.remove('hidden');
        shot.classList.add('hidden');
        btnOpen.classList.add('hidden');
        btnSnap.classList.remove('hidden');
        btnCancel.classList.remove('hidden');
        btnRetake.classList.add('hidden');
        btnClear.classList.add('hidden');
        btnFlip.classList.toggle('hidden', !canFlip);
        btnMirror.classList.remove('hidden');
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
        btnFlip.classList.add('hidden');
        btnMirror.classList.add('hidden');
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
        btnFlip.classList.add('hidden');
        btnMirror.classList.add('hidden');
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
                    facingMode: facingMode,
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
        const ctx = canvas.getContext('2d');
        if (mirrored) {
            // WYSIWYG: hasil jepret sama persis dengan preview yang dipantulkan.
            ctx.translate(w, 0);
            ctx.scale(-1, 1);
        }
        ctx.drawImage(video, 0, 0, w, h);

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

    // Balik kamera depan/belakang: matikan stream lama, ganti facingMode, buka lagi.
    btnFlip.addEventListener('click', async () => {
        if (!canFlip) return;
        stopStream();
        video.srcObject = null;
        facingMode = facingMode === 'environment' ? 'user' : 'environment';
        setStatus('');
        await openCamera();
    });

    // Mirror horizontal (kiri↔kanan): pantulkan preview video; jepretan ikut terbalik (WYSIWYG).
    btnMirror.addEventListener('click', () => {
        mirrored = !mirrored;
        video.style.transform = mirrored ? 'scaleX(-1)' : '';
    });

    // Matikan stream saat form disubmit & halaman ditinggalkan (hemat baterai/mic).
    const form = document.getElementById('story-form');
    form?.addEventListener('submit', stopStream, { once: true });
    window.addEventListener('pagehide', stopStream);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') stopStream();
    });
}
