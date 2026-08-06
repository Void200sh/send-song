// ── PENCARIAN LAGU: search → preview → resolve YT → klip/full (waveform durasi penuh) → hidden inputs ──
const input = document.getElementById('song-search');
const results = document.getElementById('song-results');
const chip = document.getElementById('song-chip');
let timer = null;

const hasSearch = Boolean(input && results && chip);
const hidden = (id) => document.getElementById(id);

// ── TOMBOL SUBMIT: terkunci sampai ada lagu yang dipilih ──
const submitBtn = document.getElementById('submit-btn');
const hasSubmit = Boolean(submitBtn);

function updateSubmitEnabled() {
    if (!hasSubmit) return;
    const ready = Boolean(
        hidden('inp-title')?.value ||
        hidden('inp-spotify-id')?.value
    );
    submitBtn.disabled = !ready;
    submitBtn.setAttribute('aria-disabled', ready ? 'false' : 'true');
}
updateSubmitEnabled();

// ── PREVIEW PLAY (preview ~30 dtk iTunes, tombol di baris hasil) ──
const previewAudio = new Audio();
previewAudio.style.display = 'none';
if (document.body) document.body.appendChild(previewAudio);
let activePreview = null;

function setPreviewBtn(btn, playing) {
    if (!btn) return;
    btn.querySelector('[data-icon-play]')?.classList.toggle('hidden', playing);
    btn.querySelector('[data-icon-pause]')?.classList.toggle('hidden', !playing);
}

function stopPreview() {
    previewAudio.pause();
    previewAudio.currentTime = 0;
    setPreviewBtn(activePreview, false);
    activePreview = null;
}

function playPreview(btn, track) {
    // Klik tombol preview yang SEDANG nyala → pause (toggle), bukan restart
    if (activePreview === btn && !previewAudio.paused) {
        stopPreview();
        return;
    }
    stopPreview();
    stopReview();
    if (!track.preview_url) return;
    activePreview = btn;
    previewAudio.src = track.preview_url;
    previewAudio.loop = false;
    previewAudio.play().catch(() => {
        setPreviewBtn(btn, false);
        activePreview = null;
    });
    setPreviewBtn(btn, true);
}

// ── ELEMEN KLIP + WAVEFORM ──
const durationBox = document.getElementById('song-duration');
const modeFullBtn = document.getElementById('mode-full');
const modeClipBtn = document.getElementById('mode-clip');
const reviewPlayBtn = document.getElementById('review-play');
const reviewIcoPlay = document.getElementById('review-ico-play');
const reviewIcoPause = document.getElementById('review-ico-pause');
const reviewTime = document.getElementById('review-time');
const clipLabel = document.getElementById('clip-label');
const waveWrap = document.getElementById('clip-waveform');
const waveStrip = document.getElementById('wave-strip');
const waveCanvas = document.getElementById('clip-wave-canvas');
const clipSelection = document.getElementById('clip-selection');
const clipPlayhead = document.getElementById('clip-playhead');
const handleL = document.getElementById('wave-handle-l');
const handleR = document.getElementById('wave-handle-r');
const waveStartLabel = document.getElementById('wave-start-label');
const waveEndLabel = document.getElementById('wave-end-label');
const waveDurLabel = document.getElementById('wave-dur-label');
const inpClipStart = hidden('inp-clip-start');
const inpClipEnd = hidden('inp-clip-end');

const hasClip = Boolean(
    durationBox && modeFullBtn && modeClipBtn && reviewPlayBtn &&
    reviewTime && clipLabel && waveWrap && waveStrip && waveCanvas && clipSelection && clipPlayhead &&
    handleL && handleR && waveStartLabel && waveEndLabel && waveDurLabel &&
    inpClipStart && inpClipEnd
);

const fmtSec = (s) => {
    if (!Number.isFinite(s) || s < 0) s = 0;
    return `${Math.floor(s / 60)}:${String(Math.round(s % 60)).padStart(2, '0')}`;
};

// State
let selectedTrack = null;
let maxSeconds = 30; // durasi lagu PENUH (waveform/ruler), bukan cuma preview
let previewLengthKnown = 30; // panjang preview iTunes (~30 dtk)
let clipMode = 'full';
let clipStart = 0;
let clipEnd = 15;
let reviewActive = false;
let reviewSource = 'none'; // 'yt' | 'preview' | 'none'
let reviewPoll = null;
let reviewStartAt = null;
let durCorrected = false; // durasi video YT sudah dikoreksi (sekali per lagu, sebelum mulai)

let stripW = 0; // lebar strip = lebar viewport (seluruh lagu dimampatkan; tanpa geser)

let waveBars = [];
let audioCtx = null;

function getAudioCtx() {
    if (!audioCtx && (window.AudioContext || window.webkitAudioContext)) {
        audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    }
    return audioCtx;
}

function previewLength() {
    const d = previewAudio.duration;
    return d && isFinite(d) ? Math.floor(d) : previewLengthKnown;
}

// ── WAVEFORM: bar asli utk bagian preview, sisanya pseudo; ruler = durasi lagu penuh ──
function pseudoBars(n, seedStr) {
    let h = 2166136261;
    for (let i = 0; i < seedStr.length; i++) h = ((h ^ seedStr.charCodeAt(i)) * 16777619) & 0xffffffff;
    const rand = () => {
        h = (h * 1664525 + 1013904223) & 0xffffffff;
        return (h >>> 0) / 4294967296;
    };
    const bars = [];
    let prev = 0.55;
    for (let i = 0; i < n; i++) {
        prev = Math.max(0.15, Math.min(1, prev + (rand() - 0.5) * 0.55));
        bars.push(prev);
    }
    return bars;
}

// ── UKURAN STRIP: seluruh lagu dimampatkan selebar viewport (seperti trimmer Instagram) → seleksi bisa menjangkau sampai akhir lagu ──
function stripMetrics() {
    return Math.max(10, waveWrap.getBoundingClientRect().width);
}

function waveBarsFor() {
    return Math.max(100, Math.min(2000, Math.round(stripMetrics() / 4))); // 1 bar ± 4 px
}

function setStrip() {
    if (!hasClip) return;
    const w = stripMetrics();
    if (stripW !== w) {
        stripW = w;
        waveStrip.style.width = `${w}px`;
        drawWave();
        renderSelection();
    }
}

function loadWave(track) {
    const seed = (track.title || '') + (track.spotify_id || '');
    const n = waveBarsFor();
    waveBars = pseudoBars(n, seed);

    // Coba decode preview → bar asli di bagian awal ruler (0..~30 dtk)
    const ctx = getAudioCtx();
    if (!ctx || !track.preview_url) return;
    fetch(track.preview_url, { mode: 'cors' })
        .then((r) => (r.ok ? r.arrayBuffer() : Promise.reject(new Error('http ' + r.status))))
        .then((buf) => ctx.decodeAudioData(buf))
        .then((audioBuffer) => {
            const data = audioBuffer.getChannelData(0);
            const n2 = 100;
            const bucket = Math.max(1, Math.floor(data.length / n2));
            const previewBars = new Array(n2);
            for (let i = 0; i < n2; i++) {
                let peak = 0;
                const start = i * bucket;
                for (let j = 0; j < bucket; j++) {
                    const v = Math.abs(data[start + j] || 0);
                    if (v > peak) peak = v;
                }
                previewBars[i] = Math.max(0.05, Math.min(1, peak * 1.5));
            }
            if (audioBuffer.duration && isFinite(audioBuffer.duration)) {
                previewLengthKnown = Math.max(1, Math.floor(audioBuffer.duration));
            }

            // Tempatkan bar asli di bagian awal ruler (proporsional), sisanya pseudo
            const cover = Math.min(1, previewLengthKnown / maxSeconds);
            const coverIdx = Math.round(cover * (n - 1));
            const realBars = new Array(coverIdx + 1);
            for (let i = 0; i <= coverIdx; i++) {
                const from = Math.min(n2 - 1, Math.round((i / Math.max(1, coverIdx)) * (n2 - 1)));
                realBars[i] = previewBars[from];
            }

            // Samakan rata-rata amplitude bar asli dengan pseudo (hindari kontras mencolok)
            const K = 8; // jumlah bar untuk crossfade di perbatasan
            let realSum = 0, pseudoSum = 0;
            for (let i = 0; i < realBars.length; i++) realSum += realBars[i];
            for (let i = Math.min(coverIdx + 1, n - 1); i < Math.min(coverIdx + 1 + K, n); i++) pseudoSum += waveBars[i];
            if (realSum > 0) {
                const target = pseudoSum > 0 ? pseudoSum / Math.max(1, Math.min(realBars.length, K)) : 0.55;
                const scale = Math.max(0.5, Math.min(2, target / (realSum / realBars.length)));
                for (let i = 0; i < realBars.length; i++) {
                    realBars[i] = Math.max(0.08, Math.min(1, realBars[i] * scale));
                }
            }

            // Bar asli di wilayahnya + crossfade ke pseudo (tanpa seam)
            for (let i = 0; i <= coverIdx && i < n; i++) waveBars[i] = realBars[i];
            for (let j = 1; j <= K && coverIdx + j < n; j++) {
                const t = j / (K + 1);
                waveBars[coverIdx + j] = waveBars[coverIdx + j] * t + realBars[coverIdx] * (1 - t);
            }
            drawWave();
        })
        .catch(() => {});
}

function drawWave() {
    if (!hasClip) return;
    const rect = waveCanvas.getBoundingClientRect();
    const w = Math.max(10, Math.round(rect.width));
    const h = Math.max(10, Math.round(rect.height));
    const dpr = window.devicePixelRatio || 1;
    if (waveCanvas.width !== Math.round(w * dpr) || waveCanvas.height !== Math.round(h * dpr)) {
        waveCanvas.width = Math.round(w * dpr);
        waveCanvas.height = Math.round(h * dpr);
    }
    const ctx = waveCanvas.getContext('2d');
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    ctx.clearRect(0, 0, w, h);

    const bars = waveBars.length ? waveBars : pseudoBars(100, selectedTrack ? selectedTrack.title : '');
    const n = bars.length;
    const slot = w / n;
    const barW = Math.max(1, Math.round(slot * 0.55));
    const isKlip = clipMode === 'klip';
    const sLeft = (clipStart / maxSeconds) * w;
    const sRight = (clipEnd / maxSeconds) * w;

    for (let i = 0; i < n; i++) {
        const cx = (i + 0.5) * slot; // pusat bar → highlight klip lebih konsisten di tepi
        const x = i * slot + (slot - barW) / 2;
        const bh = Math.max(2, bars[i] * h * 0.92);
        const inClip = isKlip && cx >= sLeft && cx <= sRight;
        ctx.fillStyle = inClip ? 'rgba(56,189,248,0.85)' : '#d4d4d8';
        if (ctx.roundRect) {
            ctx.beginPath();
            ctx.roundRect(x, (h - bh) / 2, barW, bh, barW / 2);
            ctx.fill();
        } else {
            ctx.fillRect(x, (h - bh) / 2, barW, bh);
        }
    }
}

function setClipModeBtnActive() {
    const isKlip = clipMode === 'klip';
    modeFullBtn.classList.toggle('bg-[#171717]', !isKlip);
    modeFullBtn.classList.toggle('text-white', !isKlip);
    modeFullBtn.classList.toggle('text-gray-500', isKlip);
    modeFullBtn.classList.toggle('hover:text-gray-900', isKlip);
    modeClipBtn.classList.toggle('bg-[#171717]', isKlip);
    modeClipBtn.classList.toggle('text-white', isKlip);
    modeClipBtn.classList.toggle('text-gray-500', !isKlip);
    modeClipBtn.classList.toggle('hover:text-gray-900', !isKlip);
}

function renderSelection() {
    if (!hasClip) return;
    const isKlip = clipMode === 'klip';
    const sPx = (clipStart / maxSeconds) * stripW;
    const ePx = (clipEnd / maxSeconds) * stripW;

    clipSelection.style.display = isKlip ? 'block' : 'none';
    handleL.classList.toggle('hidden', !isKlip);
    handleR.classList.toggle('hidden', !isKlip);
    if (isKlip) {
        clipSelection.style.width = `${ePx - sPx}px`;
        clipSelection.style.transform = `translateX(${sPx}px)`;
        handleL.style.transform = `translateX(${sPx}px) translateX(-50%)`;
        handleR.style.transform = `translateX(${ePx}px) translateX(-50%)`;
    }

    inpClipStart.value = isKlip ? clipStart : '';
    inpClipEnd.value = isKlip ? clipEnd : '';
    waveStartLabel.textContent = fmtSec(isKlip ? clipStart : 0);
    waveEndLabel.textContent = fmtSec(maxSeconds);
    waveDurLabel.textContent = isKlip ? `${fmtSec(clipEnd - clipStart)} dtk` : 'full lagu';
    clipLabel.textContent = isKlip ? `klip ${fmtSec(clipStart)}-${fmtSec(clipEnd)}` : 'full lagu';
}

function setClipMode(mode) {
    if (!hasClip || mode === clipMode) return;
    clipMode = mode;
    setClipModeBtnActive();
    updateSelection();
    updatePlayhead();
}

function updateSelection() {
    // Klip maks 30 dtk
    if (clipEnd - clipStart > 30) {
        clipEnd = clipStart + 30;
    }
    renderSelection();
    drawWave();
    updatePlayhead();
    if (reviewActive && clipMode === 'klip') {
        if (reviewSource === 'yt' && window.storyReview) {
            const cur = window.storyReview.time();
            if (cur < clipStart || cur > clipEnd) window.storyReview.seek(clipStart);
        } else if (reviewSource === 'preview') {
            if (previewAudio.currentTime < clipStart || previewAudio.currentTime > previewLength()) {
                previewAudio.currentTime = Math.min(clipStart, previewLength());
            }
        }
    }
}

function updatePlayhead() {
    // Playhead = posisi putar: menempel di awal klip saat diam, berjalan saat diputar
    if (!hasClip) return;
    let cur;
    if (reviewActive && reviewSource === 'yt' && window.storyReview) {
        cur = window.storyReview.time();
    } else if (reviewActive && reviewSource === 'preview') {
        cur = previewAudio.currentTime || 0;
    } else {
        cur = clipMode === 'klip' ? clipStart : 0;
    }
    cur = Math.max(0, Math.min(cur, maxSeconds));
    clipPlayhead.style.transform = `translateX(${(cur / Math.max(1, maxSeconds)) * stripW}px) translateX(-50%)`;
}

let playheadRaf = null;

function startPlayheadLoop() {
    stopPlayheadLoop();
    const step = () => {
        updatePlayhead();
        playheadRaf = reviewActive ? requestAnimationFrame(step) : null;
    };
    playheadRaf = requestAnimationFrame(step);
}

function stopPlayheadLoop() {
    if (playheadRaf) {
        cancelAnimationFrame(playheadRaf);
        playheadRaf = null;
    }
}

// ── REVIEW: tick UI per sumber pemutaran ──
function reviewTick() {
    if (!reviewActive || reviewSource !== 'yt' || !window.storyReview) return;
    const cur = window.storyReview.time();
    const dur = window.storyReview.duration();

    // Note: koreksi durasi (maxSeconds) dikerjakan SEKALI di refineMaxDurationOnce()
    // sebelum mulai putar — sengaja TIDAK di sini agar kotak seleksi tidak bergeser sendiri saat play.

    if (clipMode === 'klip') {
        const end = Math.min(clipEnd, dur > 0 ? dur : maxSeconds);
        const span = Math.max(0, end - clipStart);
        if (cur >= end || cur < clipStart) {
            window.storyReview.seek(clipStart);
            return;
        }
        reviewTime.textContent = `${fmtSec(cur - clipStart)} / ${fmtSec(span)}`;
    } else {
        const total = dur > 0 ? dur : maxSeconds;
        reviewTime.textContent = `${fmtSec(cur)} / ${fmtSec(total)}`;
    }
    updatePlayhead();
}

function reviewTickPreview() {
    if (!reviewActive || reviewSource !== 'preview') return;
    const cur = previewAudio.currentTime;
    const pd = previewLength();
    if (clipMode === 'klip') {
        const end = Math.min(clipEnd, pd);
        const span = Math.max(0, end - clipStart);
        if (cur >= end || cur < clipStart) {
            previewAudio.currentTime = clipStart;
            return;
        }
        reviewTime.textContent = `${fmtSec(cur - clipStart)} / ${fmtSec(span)}`;
    } else {
        reviewTime.textContent = `${fmtSec(Math.min(cur, pd))} / ${fmtSec(pd)}`;
    }
    updatePlayhead();
}

function startReviewPoll() {
    stopReviewPoll();
    reviewPoll = setInterval(reviewTick, 400);
}

function stopReviewPoll() {
    if (reviewPoll) {
        clearInterval(reviewPoll);
        reviewPoll = null;
    }
}

// Koreksi ruler ke durasi asli video YouTube — SEKALI per lagu, TANPA memblokir putar.
// (Kalau dilakukan sambil putar terus-menerus, kotak seleksi & waveform ikut bergeser sendiri.)
async function refineMaxDurationBehindPlayback() {
    if (!hasClip || durCorrected || !window.storyReview) return;
    for (let i = 0; i < 30; i++) {
        if (!window.storyReview.isLoaded()) {
            await new Promise((r) => setTimeout(r, 150));
            continue;
        }
        const d = window.storyReview.duration();
        if (d > 0 && isFinite(d)) {
            durCorrected = true;
            const t = Math.round(d);
            if (Math.abs(t - maxSeconds) > 2) {
                maxSeconds = t;
                if (clipEnd > maxSeconds) clipEnd = maxSeconds;
                if (clipStart > Math.max(0, maxSeconds - 1)) clipStart = Math.max(0, maxSeconds - 1);
                renderSelection();
                drawWave();
                updatePlayhead();
            }
            return;
        }
        await new Promise((r) => setTimeout(r, 150));
    }
}

// ── REVIEW PLAYER (▶ toggle pause/resume; full & klip pakai video YouTube asli) ──
function stopReview() {
    stopReviewPoll();
    stopPlayheadLoop();
    reviewActive = false;
    previewAudio.pause();
    window.storyReview?.pause();
    reviewIcoPlay.classList.remove('hidden');
    reviewIcoPause.classList.add('hidden');
    updatePlayhead();
}

async function startReview() {
    if (!hasClip || !selectedTrack) return;
    stopPreview();
    reviewActive = true;
    reviewIcoPlay.classList.add('hidden');
    reviewIcoPause.classList.remove('hidden');

    const vid = hidden('inp-youtube-id') ? hidden('inp-youtube-id').value : '';
    const start = reviewStartAt != null ? reviewStartAt : (clipMode === 'klip' ? clipStart : 0);
    reviewStartAt = null;

    updatePlayhead();
    startPlayheadLoop();

    if (vid && window.storyReview) {
        reviewSource = 'yt';
        window.storyReview.on('ended', () => stopReview());
        window.storyReview.on('error', () => previewFallbackAt(Math.max(0, Math.min(start, previewLength() - 1))));
        await window.storyReview.ensureReady();
        if (!reviewActive) return; // user berhenti sementara loading
        const loaded = window.storyReview.isLoaded();
        // full lagu terus video sama & lagi pause → lanjut dari posisi yang dipilih
        if (clipMode === 'full' && loaded && window.storyReview.videoId === vid) {
            const st = window.storyReview.state();
            if (st === 2 || st === 5) {
                window.storyReview.seek(start);
                window.storyReview.resume();
                startReviewPoll();
                updatePlayhead();
                refineMaxDurationBehindPlayback();
                return;
            }
        }
        window.storyReview.play(vid, start);
        startReviewPoll();
        updatePlayhead();
        // Koreksi durasi ruler TIDAK memblokir putar (fire & skip kalau belum siap).
        // Ukur sekali begitu durasi video ketemu; diamankan bila Editor tak punya player.
        refineMaxDurationBehindPlayback();
        return;
    }
    previewFallbackAt(start);
}

function previewFallbackAt(at) {
    reviewSource = 'preview';
    if (!selectedTrack || !selectedTrack.preview_url) {
        stopReview();
        return;
    }
    previewAudio.src = selectedTrack.preview_url;
    previewAudio.loop = false;
    const pd = previewLength();
    if (clipMode === 'klip') {
        at = Math.min(at, Math.max(0, pd - 1));
    } else if (!at || at >= pd - 0.25) {
        at = 0;
    }
    previewAudio.currentTime = at;
    previewAudio.play().catch(() => stopReview());
    updatePlayhead();
}

function seekReviewAt(sec) {
    if (!hasClip || !selectedTrack) return;
    if (clipMode === 'klip') {
        sec = Math.max(clipStart, Math.min(sec, clipEnd));
    } else {
        sec = Math.max(0, Math.min(sec, maxSeconds));
    }
    if (reviewActive) {
        if (reviewSource === 'yt' && window.storyReview) {
            window.storyReview.seek(sec);
            if (window.storyReview.state() !== 1) window.storyReview.resume();
        } else if (reviewSource === 'preview') {
            previewAudio.currentTime = Math.min(sec, previewLength());
            if (previewAudio.paused) previewAudio.play().catch(() => stopReview());
        }
        updatePlayhead();
        return;
    }
    reviewStartAt = sec;
    startReview();
}

previewAudio.addEventListener('ended', () => {
    setPreviewBtn(activePreview, false);
    activePreview = null;
    if (reviewSource === 'preview' && reviewActive) stopReview();
});

previewAudio.addEventListener('error', () => {
    setPreviewBtn(activePreview, false);
    activePreview = null;
    if (reviewSource === 'preview' && reviewActive) stopReview();
});

previewAudio.addEventListener('timeupdate', () => {
    if (reviewSource === 'preview' && reviewActive) reviewTickPreview();
});

function showDuration() {
    if (hasClip) durationBox.classList.remove('hidden');
}

function resetClip() {
    if (!hasClip) return;
    selectedTrack = null;
    stopReview();
    reviewSource = 'none';
    reviewStartAt = null;
    clipMode = 'full';
    setClipModeBtnActive();
    clipStart = 0;
    clipEnd = 15;
    maxSeconds = 30;
    previewLengthKnown = 30;
    durCorrected = false;
    waveBars = [];
    setStrip();
    renderSelection();
    updatePlayhead();
    const ctx = waveCanvas.getContext('2d');
    if (ctx) ctx.clearRect(0, 0, waveCanvas.width, waveCanvas.height);
    reviewTime.textContent = '0:00 / 0:00';
}

function selectTrackState(track) {
    selectedTrack = track;
    const ms = Math.round((track.duration_ms || 0) / 1000);
    maxSeconds = Math.max(30, ms); // ruler = durasi lagu penuh
    previewLengthKnown = 30;
    clipMode = 'full';
    clipStart = 0;
    clipEnd = Math.min(15, maxSeconds);
    reviewSource = 'none';
    reviewStartAt = null;
    durCorrected = false;
    stopReviewPoll();
    window.storyReview?.pause();
    setClipModeBtnActive();
    showDuration();
    setStrip();
    renderSelection();
    updatePlayhead();
    loadWave(track);
    previewAudio.src = track.preview_url || '';
    previewAudio.pause();
}

// ── INTERAKSI TRIM: drag handle L/R (min 1 dtk, maks 30 dtk), drag kotak pilihan, tap → seek (tanpa pan) ──
let dragHandle = null; // 'l' | 'r' | 'window' | null
let downX = 0;
let winDown = null; // posisi awal (x strip, clipStart, clipEnd) saat drag kotak
let tapPending = null; // {x} utk dibedakan tap vs geser (geser = batal, tanpa pan)

function stripX(clientX) {
    return clientX - waveWrap.getBoundingClientRect().left;
}

if (hasClip) {
    waveWrap.addEventListener('pointerdown', (e) => {
        const isKlip = clipMode === 'klip';
        tapPending = { x: stripX(e.clientX), t: Date.now() };
        downX = e.clientX;

        if (isKlip && e.target.closest('#wave-handle-l')) {
            e.preventDefault();
            dragHandle = 'l';
            tapPending = null;
            waveWrap.setPointerCapture(e.pointerId);
            moveHandle(e);
        } else if (isKlip && e.target.closest('#wave-handle-r')) {
            e.preventDefault();
            dragHandle = 'r';
            tapPending = null;
            waveWrap.setPointerCapture(e.pointerId);
            moveHandle(e);
        } else if (isKlip && e.target.closest('#clip-selection')) {
            e.preventDefault();
            dragHandle = 'window';
            winDown = { x: stripX(e.clientX), s0: clipStart, e0: clipEnd };
            waveWrap.setPointerCapture(e.pointerId);
        } else {
            // background: diam = tap (seek), digeser = batal
            waveWrap.setPointerCapture(e.pointerId);
        }
    });

    waveWrap.addEventListener('pointermove', (e) => {
        if (dragHandle === 'l' || dragHandle === 'r') {
            moveHandle(e);
            return;
        }
        if (dragHandle === 'window') {
            if (tapPending && (Math.abs(e.clientX - downX) > 5 || Date.now() - tapPending.t > 350)) {
                tapPending = null;
            }
            if (!tapPending) {
                const x = stripX(e.clientX);
                const span = winDown.e0 - winDown.s0;
                const ns = Math.max(0, Math.min(Math.round(winDown.s0 + ((x - winDown.x) / Math.max(1, stripW)) * maxSeconds), maxSeconds - span));
                if (ns !== clipStart) {
                    clipStart = ns;
                    clipEnd = ns + span;
                    updateSelection();
                }
            }
            return;
        }
        if (tapPending && (Math.abs(e.clientX - downX) > 5 || Date.now() - tapPending.t > 350)) {
            tapPending = null;
        }
    });

    const endDrag = () => {
        if (tapPending) {
            const sec = Math.round((tapPending.x / Math.max(1, stripW)) * maxSeconds);
            seekReviewAt(sec);
        }
        dragHandle = null;
        winDown = null;
        tapPending = null;
    };
    waveWrap.addEventListener('pointerup', endDrag);
    waveWrap.addEventListener('pointercancel', endDrag);

    function moveHandle(e) {
        const sec = Math.max(0, Math.min(Math.round((stripX(e.clientX) / Math.max(1, stripW)) * maxSeconds), maxSeconds));
        if (dragHandle === 'l') {
            // kiri: boleh geser, tapi span maks 30 dtk (tidak melewati batas kanan)
            const lo = Math.max(0, clipEnd - 30);
            clipStart = Math.max(lo, Math.min(sec, clipEnd - 1, maxSeconds - 1));
            updateSelection();
        } else if (dragHandle === 'r') {
            // kanan: maks 30 dtk dari mulai
            const hi = Math.min(maxSeconds, clipStart + 30);
            clipEnd = Math.max(clipStart + 1, Math.min(sec, hi));
            updateSelection();
        }
    }

    modeFullBtn.addEventListener('click', () => setClipMode('full'));
    modeClipBtn.addEventListener('click', () => setClipMode('klip'));

    reviewPlayBtn.addEventListener('click', () => {
        if (reviewActive) stopReview();
        else startReview();
    });

    window.addEventListener('resize', () => {
        setStrip();
        drawWave();
    });
}

// ── DROPDOWN HASIL PENCARIAN ──
const fmtDuration = (ms) => {
    const m = Math.floor(ms / 60000);
    const s = String(Math.round((ms % 60000) / 1000)).padStart(2, '0');
    return `${m}:${s}`;
};

const trackRow = (t) => `
    <div data-track data-json='${JSON.stringify(t).replace(/'/g, '&#39;')}' role="button" tabindex="0"
        class="w-full flex items-center gap-3 p-2 hover:bg-gray-50 text-left cursor-pointer">
        <img src="${t.cover_url}" class="w-9 h-9 rounded object-cover" alt="">
        <span class="flex-1 min-w-0">
            <span class="block text-sm truncate">${t.title}</span>
            <span class="block text-xs text-gray-500 truncate">${t.artist}</span>
        </span>
        <span class="text-[11px] text-gray-400 whitespace-nowrap">${fmtDuration(t.duration_ms)}</span>
        ${t.preview_url ? `
        <button type="button" data-preview aria-label="putar preview"
            class="w-10 h-10 flex-none aspect-square rounded-full bg-[#171717] hover:bg-gray-800 text-white flex items-center justify-center cursor-pointer">
            <svg data-icon-play class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.8a1 1 0 0 1 1.5-.9l10 6a1 1 0 0 1 0 1.7l-10 6a1 1 0 0 1-1.5-.9V2.8z"/></svg>
            <svg data-icon-pause class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M6 4a1 1 0 0 1 1 1v10a1 1 0 1 1-2 0V5a1 1 0 0 1 1-1zm8 0a1 1 0 0 1 1 1v10a1 1 0 1 1-2 0V5a1 1 0 0 1 1-1z"/></svg>
        </button>` : ''}
    </div>`;

async function fetchJson(url, opts) {
    const res = await fetch(url, opts);
    if (!res.ok) throw new Error(res.status);
    return res.json();
}

async function selectTrack(track) {
    stopPreview();
    stopReview();
    results.classList.add('hidden');
    chip.classList.remove('hidden');
    chip.innerHTML = `
        <img src="${track.cover_url}" class="w-9 h-9 rounded object-cover" alt="">
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium truncate">${track.title}</p>
            <p class="text-xs text-gray-500 truncate">${track.artist}</p>
        </div>
        <span id="resolve-status" class="text-xs text-gray-400">mencari audio...</span>
        <button type="button" id="chip-remove" class="text-gray-400 hover:text-gray-700 cursor-pointer bg-transparent border-0 p-1" aria-label="hapus lagu">✕</button>`;

    hidden('inp-spotify-id').value = track.spotify_id;
    hidden('inp-title').value = track.title;
    hidden('inp-artist').value = track.artist;
    hidden('inp-cover').value = track.cover_url ?? '';
    updateSubmitEnabled();

    selectTrackState(track);

    try {
        const data = await fetchJson('/api/songs/resolve', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ title: track.title, artist: track.artist }),
        });
        hidden('inp-youtube-id').value = data.youtube_id ?? '';
        document.getElementById('resolve-status').textContent =
            data.youtube_id ? '✓ audio siap' : 'audio tidak ditemukan (fallback)';
        updateSubmitEnabled();
    } catch {
        hidden('inp-youtube-id').value = '';
        document.getElementById('resolve-status').textContent = 'gagal resolve';
    }
}

if (hasSearch) {
    input.addEventListener('input', () => {
        clearTimeout(timer);
        const q = input.value.trim();
        if (q.length < 2) {
            stopPreview();
            results.classList.add('hidden');
            return;
        }
        timer = setTimeout(async () => {
            stopPreview();
            try {
                const data = await fetchJson(`/api/songs/search?q=${encodeURIComponent(q)}`);
                results.innerHTML = data.tracks.length
                    ? data.tracks.map(trackRow).join('')
                    : '<p class="p-3 text-sm text-gray-500">Tidak ditemukan</p>';
                results.classList.remove('hidden');
            } catch {
                results.innerHTML = '<p class="p-3 text-sm text-gray-500">Gagal mencari lagu</p>';
                results.classList.remove('hidden');
            }
        }, 400);
    });

    results.addEventListener('click', (e) => {
        const previewBtn = e.target.closest('[data-preview]');
        if (previewBtn) {
            const row = previewBtn.closest('[data-track]');
            if (row) playPreview(previewBtn, JSON.parse(row.dataset.json));
            return;
        }
        const btn = e.target.closest('[data-track]');
        if (btn) selectTrack(JSON.parse(btn.dataset.json));
    });

    results.addEventListener('keydown', (e) => {
        const row = e.target.closest('[data-track]');
        if (row && (e.key === 'Enter' || e.key === ' ')) {
            e.preventDefault();
            if (e.target.closest('[data-preview]')) return;
            selectTrack(JSON.parse(row.dataset.json));
        }
    });

    chip.addEventListener('click', (e) => {
        if (!e.target.closest('#chip-remove')) return;
        chip.classList.add('hidden');
        ['inp-spotify-id', 'inp-title', 'inp-artist', 'inp-cover', 'inp-youtube-id']
            .forEach((id) => {
                hidden(id).value = '';
            });
        input.value = '';
        stopPreview();
        stopReview();
        if (hasClip) durationBox.classList.add('hidden');
        updateSubmitEnabled();
        resetClip();
    });
}