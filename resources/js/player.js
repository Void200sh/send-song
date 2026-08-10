// ── CUSTOM PLAYER: YouTube IFrame API di balik iframe tersembunyi ──
let player = null;
let activeCard = null;
let pendingCard = null;
let apiReady = false;
let playerReady = false;   // onReady player YouTube sudah kepanggil
let currentVideoId = null; // video yang lagi dimuat di player (buat deteksi pre-warm)
let playerHasVideo = false; // player sudah punya video (di-cue/dimuat) — biar fast-path gak salah
let pollTimer = null;
let seeking = false;

const fmt = (sec) => {
    if (!Number.isFinite(sec)) return '0:00';
    return `${Math.floor(sec / 60)}:${String(Math.floor(sec % 60)).padStart(2, '0')}`;
};

// Klip custom dari data-clip-start / data-clip-end (kosong = full lagu)
// Return { start, end } atau null
function clipFor(card) {
    if (!card) return null;
    const start = parseInt(card.dataset.clipStart, 10);
    const end = parseInt(card.dataset.clipEnd, 10);
    if (Number.isFinite(start) && Number.isFinite(end) && end > start) {
        return { start, end };
    }
    return null;
}

let apiScriptLoading = false;

function loadYouTubeApi() {
    return new Promise((resolve) => {
        if (window.YT && window.YT.Player) return resolve();
        if (!apiScriptLoading) {
            apiScriptLoading = true;
            const tag = document.createElement('script');
            tag.src = 'https://www.youtube.com/iframe_api';
            document.head.appendChild(tag);
            window.onYouTubeIframeAPIReady = () => resolve();
        } else {
            const prev = window.onYouTubeIframeAPIReady;
            window.onYouTubeIframeAPIReady = () => {
                prev?.();
                resolve();
            };
        }
    });
}

function startPolling() {
    stopPolling();
    pollTimer = setInterval(() => {
        if (!player || seeking || !activeCard) return;
        const cur = player.getCurrentTime();
        const dur = player.getDuration();
        const clip = clipFor(activeCard);
        if (clip) {
            // Klip [start, end]: lewat batas → loop balik ke start
            if (cur >= clip.end) {
                player.seekTo(clip.start, true);
                return;
            }
            if (cur < clip.start) {
                player.seekTo(clip.start, true);
                return;
            }
            const span = clip.end - clip.start;
            activeCard.querySelector('[data-progress]').style.width = `${((cur - clip.start) / span) * 100}%`;
            activeCard.querySelector('[data-duration]').textContent = fmt(span);
        } else if (dur > 0) {
            activeCard.querySelector('[data-progress]').style.width = `${(cur / dur) * 100}%`;
        }
        activeCard.querySelector('[data-current]').textContent = fmt(cur);
    }, 500);
}

function stopPolling() {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
}

// Kembalikan UI kartu ke keadaan "belum diputar" (ikon play, progress & waktu nol)
// Dipanggil buat kartu yang TIDAK aktif lagi, biar gak kelihatan "lagi nyanyi" semua
function resetCardUi(card) {
    if (!card) return;
    card.querySelector('[data-icon-play]')?.classList.remove('hidden');
    card.querySelector('[data-icon-pause]')?.classList.add('hidden');
    const progress = card.querySelector('[data-progress]');
    if (progress) progress.style.width = '0%';
    const current = card.querySelector('[data-current]');
    if (current) current.textContent = '0:00';
}

function setActiveCard(card) {
    document.querySelectorAll('[data-player-card]').forEach((c) => {
        c.classList.toggle('ring-2', c === card);
        c.classList.toggle('ring-gray-900', c === card);
        // Reset semua kartu lain biar cuma SATU lagu yang tampak/sedang diputar
        if (c !== card) resetCardUi(c);
    });
    activeCard = card;
}

function setPlayingState(playing) {
    if (!activeCard) return;
    activeCard.querySelector('[data-icon-play]').classList.toggle('hidden', playing);
    activeCard.querySelector('[data-icon-pause]').classList.toggle('hidden', !playing);
}

// Baca durasi asli dari player setelah video di-cue (metadata butuh sesaat),
// buat kartu yang labelnya masih "0:00" (pesan lama tanpa durasi di DB).
function fillRealDuration(durEl) {
    if (!durEl) return;
    let tries = 0;
    const read = () => {
        if (!player) return;
        const d = player.getDuration();
        if (d > 0 && isFinite(d)) {
            durEl.textContent = fmt(d);
            return;
        }
        if (++tries < 24) setTimeout(read, 250);
    };
    setTimeout(read, 300);
}

function createPlayer(card, autoplay = true) {
    currentVideoId = card.dataset.videoId;
    // PENTING: player dibuat TANPA videoId. Konstruktor YT.Player yang diberi videoId
    // berperilaku seperti loadVideoById — bisa langsung MUTER otomatis di browser yang
    // mengizinkan autoplay (Media Engagement Index tinggi). Itulah kenapa pas halaman
    // browse/detail dibuka, lagu terbaru (kartu pertama hasil pre-warm) ikut bunyi sendiri.
    // Sekarang video pre-warm di-cue manual via cueVideoById (siap TANPA muter),
    // dan autoplay:0 dipasang eksplisit sebagai pengaman ganda.
    player = new YT.Player('yt-audio-host', {
        width: 1,
        height: 1,
        playerVars: {
            playsinline: 1,
            controls: 0,
            disablekb: 1,
            fs: 0,
            rel: 0,
            modestbranding: 1,
            autoplay: 0,
        },
        events: {
            onReady: () => {
                playerReady = true;
                const cardRef = activeCard || card;
                const clip = clipFor(cardRef);
                const durEl = cardRef?.querySelector('[data-duration]');
                if (clip && durEl) durEl.textContent = fmt(clip.end - clip.start);

                // Klik yang masuk pas player masih init → jalanin sekarang (paling prioritas)
                if (pendingCard) {
                    // Cue dulu video pre-warm biar playCard bisa fast-path kalau kartunya sama
                    if (clip) player.cueVideoById(currentVideoId, clip.start);
                    else player.cueVideoById(currentVideoId);
                    playerHasVideo = true;
                    const pending = pendingCard;
                    pendingCard = null;
                    playCard(pending);
                    return;
                }
                if (autoplay) {
                    // User KLIK play sebelum API siap → putar sekarang (ada izin user).
                    // playerHasVideo masih false di sini, jadi playCard lewat cabang
                    // loadVideoById + play — hasilnya sama, tanpa duplikasi logika.
                    playCard(card);
                    return;
                }
                // PRE-WARM TANPA MUTER: cue video (metadata dimuat, tidak mulai putar).
                // Label durasi asli di-refresh begitu metadata masuk (buat lagu tanpa durasi DB).
                if (clip) player.cueVideoById(currentVideoId, clip.start);
                else {
                    player.cueVideoById(currentVideoId);
                    if (durEl) fillRealDuration(durEl);
                }
                playerHasVideo = true;
            },
            onStateChange: (e) => {
                if (e.data === YT.PlayerState.PLAYING) {
                    setPlayingState(true);
                    startPolling();
                }
                if (e.data === YT.PlayerState.PAUSED) {
                    setPlayingState(false);
                    stopPolling();
                }
                if (e.data === YT.PlayerState.ENDED) {
                    setPlayingState(false);
                    stopPolling();
                }
            },
            onError: () => {
                stopPolling();
                setPlayingState(false);
                const fallback = activeCard?.querySelector('[data-fallback]');
                if (fallback) fallback.classList.remove('hidden');
            },
        },
    });
}

async function ensureApiReady() {
    if (apiReady) return;
    await loadYouTubeApi();
    apiReady = true;
}

function toggleCard(card) {
    // Klik tombol di kartu yang SAMA dengan yang sedang aktif → toggle play/pause
    if (player && activeCard === card && playerReady) {
        const state = player.getPlayerState();

        if (state === YT.PlayerState.PLAYING) {
            player.pauseVideo();
        } else if (state === YT.PlayerState.PAUSED || state === YT.PlayerState.CUED) {
            player.playVideo();
        } else {
            // ENDED / BUFFERING / belum siap → mainkan (atau ulang dari awal kalo sudah selesai)
            player.playVideo();
        }
        return;
    }

    // Player masih init (mis. lagi pre-warm) → simpan dulu, diputar begitu siap
    if (player && !playerReady) {
        pendingCard = card;
        return;
    }

    playCard(card);
}

// Mainkan satu kartu: jadikan aktif + muat videonya (kalo beda) + putar
function playCard(card) {
    setActiveCard(card);
    card.querySelector('[data-fallback]')?.classList.add('hidden');

    if (player) {
        if (playerHasVideo && currentVideoId === card.dataset.videoId) {
            // Video yang sama udah di-cue/dimuat (pre-warm / baru diputar) → langsung jalan, instan
            player.playVideo();
        } else {
            // Lagu beda → muat & putar. Iframe yang sama otomatis matiin audio sebelumnya,
            // gak perlu stopVideo() — itu cuma nambah jeda unload → reload.
            // Klip: mulai langsung dari detik awal, gak nunggu polling (500ms) lompat ke sana.
            const clip = clipFor(card);
            if (clip) {
                player.loadVideoById(card.dataset.videoId, clip.start);
            } else {
                player.loadVideoById(card.dataset.videoId);
            }
            currentVideoId = card.dataset.videoId;
            playerHasVideo = true;
            player.playVideo();
        }
    } else {
        createPlayer(card);
    }
}

// Delegasi event global — satu listener untuk semua kartu
document.addEventListener('click', (e) => {
    const playBtn = e.target.closest('[data-play]');
    if (playBtn) {
        // Kartu sekarang adalah <a> — cegah navigasi kalau yang diklik tombol play
        e.preventDefault();
        const card = playBtn.closest('[data-player-card]');
        if (!card) return;

        // Kalau API YouTube belum siap, simpan kartu & eksekusi begitu API ready
        if (!apiReady) {
            pendingCard = card;
            ensureApiReady();
            return;
        }

        toggleCard(card);
        return;
    }

    const fallbackBtn = e.target.closest('[data-fallback]');
    if (fallbackBtn) {
        e.preventDefault();
        if (player && activeCard) {
            window.open(`https://www.youtube.com/watch?v=${activeCard.dataset.videoId}`, '_blank');
        } else if (fallbackBtn.dataset.url) {
            window.open(fallbackBtn.dataset.url, '_blank');
        }
        return;
    }

    // Tombol "buka lagu di Spotify" (data lama tanpa YouTube) — buka URL dari data-open-url
    const openUrlBtn = e.target.closest('[data-open-url]');
    if (openUrlBtn) {
        e.preventDefault();
        if (openUrlBtn.dataset.openUrl) window.open(openUrlBtn.dataset.openUrl, '_blank');
        return;
    }

    // Klik di seekbar di dalam kartu — jangan navigasi, biar bisa drag
    if (e.target.closest('[data-seekbar]')) {
        e.preventDefault();
        return;
    }

    // Klik kartu → buka halaman detail
    // Kecuali: klik di tombol play, seekbar, link fallback, atau link lain di dalam kartu
    const card = e.target.closest('[data-msg-card]');
    if (card && card.dataset.detailUrl) {
        if (e.target.closest('[data-play]') || e.target.closest('[data-seekbar]') || e.target.closest('a, button')) {
            return;
        }
        window.location.href = card.dataset.detailUrl;
    }
});

// Seekbar: input (drag) vs change (lepas) biar polling gak konflik
document.addEventListener('input', (e) => {
    const slider = e.target.closest('[data-seekbar]');
    if (!slider || !player) return;
    seeking = true;
    const pct = (slider.value / slider.max) * 100;
    slider.closest('[data-player-card]').querySelector('[data-progress]').style.width = `${pct}%`;
});

document.addEventListener('change', (e) => {
    const slider = e.target.closest('[data-seekbar]');
    if (!slider || !player) return;
    const dur = player.getDuration();
    const clip = clipFor(activeCard);
    const cap = clip ? clip.end - clip.start : dur;
    if (cap > 0) {
        const at = clip ? clip.start + (slider.value / slider.max) * cap : (slider.value / slider.max) * cap;
        player.seekTo(at, true);
    }
    setTimeout(() => {
        seeking = false;
    }, 100);
});

// ── INIT PLAYER: host iframe tersembunyi + muat API SEPAKET (gak nunggu window.load) ──
// Module script jalan SETELAH DOM selesai di-parse, jadi body udah pasti ada.
// API YouTube mulai didownload bareng aset lain — bukan nunggu semua gambar/font selesai.
(async function initPlayer() {
    const host = document.createElement('div');
    host.id = 'yt-audio-host';
    host.style.cssText = 'position:fixed;left:-9999px;top:-9999px;opacity:0;width:1px;height:1px;pointer-events:none;z-index:-1;';
    (document.body || document.documentElement).appendChild(host);

    await loadYouTubeApi();
    apiReady = true;

    // Ada klik play yang masuk sebelum API siap → buat player + langsung main.
    // Kalo nggak → PRE-WARM: bikin player & cue video pertama TANPA autoplay,
    // biar klik play pertama tinggal playVideo() — gak nunggu bikin iframe & load video.
    // GAK ADA AUTOPLAY DI MANA PUN — lagu cuma muter kalau user klik tombol play.
    if (!player) {
        const card = pendingCard || document.querySelector('[data-player-card]');
        if (card) {
            const autoplay = !!pendingCard;
            pendingCard = null;
            createPlayer(card, autoplay);
        }
    }
})();

// ── REVIEW FULL/KLIP (halaman story) — pakai player YT tersembunyi yang sama ──
// Kartu pesan & review tidak pernah satu halaman, jadi aman berbagi instance `player`.
let reviewPlayerCreated = false;
const reviewHandlers = { ended: null, error: null };

function createReviewPlayer(videoId, startSec) {
    reviewPlayerCreated = true;
    currentVideoId = videoId;
    player = new YT.Player('yt-audio-host', {
        videoId,
        width: 1,
        height: 1,
        playerVars: {
            playsinline: 1,
            controls: 0,
            disablekb: 1,
            fs: 0,
            rel: 0,
            modestbranding: 1,
            start: startSec || 0,
        },
        events: {
            onReady: () => {
                playerReady = true;
                player.playVideo();
            },
            onStateChange: (e) => {
                if (e.data === YT.PlayerState.ENDED) reviewHandlers.ended?.();
            },
            onError: () => {
                reviewHandlers.error?.();
            },
        },
    });
}

window.storyReview = {
    ensureReady() {
        return ensureApiReady();
    },
    isLoaded() {
        return reviewPlayerCreated && !!player;
    },
    videoId: '',
    play(videoId, startSec) {
        this.videoId = videoId;
        currentVideoId = videoId;
        if (!reviewPlayerCreated || !player) {
            createReviewPlayer(videoId, startSec);
            return;
        }
        if (startSec && startSec > 0) player.loadVideoById(videoId, startSec);
        else player.loadVideoById(videoId);
        player.playVideo();
    },
    resume() {
        player?.playVideo();
    },
    pause() {
        player?.pauseVideo();
    },
    seek(sec) {
        player?.seekTo(sec, true);
    },
    time() {
        return player ? player.getCurrentTime() : 0;
    },
    duration() {
        return player ? player.getDuration() : 0;
    },
    state() {
        return player ? player.getPlayerState() : -1;
    },
    on(event, fn) {
        reviewHandlers[event] = fn;
    },
};
