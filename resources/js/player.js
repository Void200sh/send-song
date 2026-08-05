// ── CUSTOM PLAYER: YouTube IFrame API di balik iframe tersembunyi ──
let player = null;
let activeCard = null;
let pendingCard = null;
let apiReady = false;
let pollTimer = null;
let seeking = false;

const fmt = (sec) => {
    if (!Number.isFinite(sec)) return '0:00';
    return `${Math.floor(sec / 60)}:${String(Math.floor(sec % 60)).padStart(2, '0')}`;
};

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
        if (dur > 0) {
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

function setActiveCard(card) {
    document.querySelectorAll('[data-player-card]').forEach((c) => {
        c.classList.toggle('ring-2', c === card);
        c.classList.toggle('ring-gray-900', c === card);
    });
    activeCard = card;
}

function setPlayingState(playing) {
    if (!activeCard) return;
    activeCard.querySelector('[data-icon-play]').classList.toggle('hidden', playing);
    activeCard.querySelector('[data-icon-pause]').classList.toggle('hidden', !playing);
}

function createPlayer(card) {
    player = new YT.Player('yt-audio-host', {
        videoId: card.dataset.videoId,
        width: 1,
        height: 1,
        playerVars: {
            playsinline: 1,
            controls: 0,
            disablekb: 1,
            fs: 0,
            rel: 0,
            modestbranding: 1,
        },
        events: {
            onReady: () => {
                activeCard.querySelector('[data-duration]').textContent = fmt(player.getDuration());
                player.playVideo();
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
    if (player && activeCard === card) {
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

    // Kartu yang diklik BEDA dari yang aktif → ganti lagu + autoplay
    setActiveCard(card);
    card.querySelector('[data-fallback]')?.classList.add('hidden');

    if (player) {
        player.loadVideoById(card.dataset.videoId);
        player.playVideo();
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
    if (dur > 0) {
        player.seekTo((slider.value / slider.max) * dur, true);
    }
    setTimeout(() => {
        seeking = false;
    }, 100);
});

// Host iframe tersembunyi (1×1px, opacity 0, di luar layar)
window.addEventListener('load', () => {
    const host = document.createElement('div');
    host.id = 'yt-audio-host';
    host.style.cssText = 'position:fixed;left:-9999px;top:-9999px;opacity:0;width:1px;height:1px;pointer-events:none;z-index:-1;';
    document.body.appendChild(host);
    loadYouTubeApi().then(() => {
        apiReady = true;
        // Kalo user sempet klik play sebelum API siap, jalankan sekarang
        if (pendingCard) {
            toggleCard(pendingCard);
            pendingCard = null;
        }
    });
});
