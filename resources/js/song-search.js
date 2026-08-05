// ── PENCARIAN LAGU: Spotify search → resolve YouTube → hidden inputs ──
// Catatan: gak pakai top-level `return` (bikin SyntaxError di module), jadi tiap listener di-guard aja.
const input = document.getElementById('song-search');
const results = document.getElementById('song-results');
const chip = document.getElementById('song-chip');
let timer = null;

const hasSearch = Boolean(input && results && chip);

const hidden = (id) => document.getElementById(id);

async function fetchJson(url, opts) {
    const res = await fetch(url, opts);
    if (!res.ok) throw new Error(res.status);
    return res.json();
}

const fmtDuration = (ms) => {
    const m = Math.floor(ms / 60000);
    const s = String(Math.round((ms % 60000) / 1000)).padStart(2, '0');
    return `${m}:${s}`;
};

async function selectTrack(track) {
    results.classList.add('hidden');
    chip.classList.remove('hidden');
    chip.innerHTML = `
        <img src="${track.cover_url}" class="w-9 h-9 rounded object-cover" alt="">
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium truncate">${track.title}</p>
            <p class="text-xs text-gray-500 truncate">${track.artist}</p>
        </div>
        <span id="resolve-status" class="text-xs text-gray-400">mencari audio...</span>
        <button type="button" id="chip-remove" class="text-gray-400 hover:text-gray-700" aria-label="hapus lagu">✕</button>`;

    hidden('inp-spotify-id').value = track.spotify_id;
    hidden('inp-title').value = track.title;
    hidden('inp-artist').value = track.artist;
    hidden('inp-cover').value = track.cover_url ?? '';

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
            results.classList.add('hidden');
            return;
        }
        timer = setTimeout(async () => {
            try {
                const data = await fetchJson(`/api/songs/search?q=${encodeURIComponent(q)}`);
                results.innerHTML = data.tracks.length
                    ? data.tracks.map((t) => `
                        <button type="button" data-track data-json='${JSON.stringify(t).replace(/'/g, '&#39;')}'
                            class="w-full flex items-center gap-3 p-2 hover:bg-gray-50 text-left">
                            <img src="${t.cover_url}" class="w-9 h-9 rounded object-cover" alt="">
                            <span class="flex-1 min-w-0">
                                <span class="block text-sm truncate">${t.title}</span>
                                <span class="block text-xs text-gray-500 truncate">${t.artist}</span>
                            </span>
                            <span class="text-[11px] text-gray-400">${fmtDuration(t.duration_ms)}</span>
                        </button>`).join('')
                    : '<p class="p-3 text-sm text-gray-500">Tidak ditemukan</p>';
                results.classList.remove('hidden');
            } catch {
                results.innerHTML = '<p class="p-3 text-sm text-gray-500">Gagal mencari lagu</p>';
                results.classList.remove('hidden');
            }
        }, 400);
    });

    results.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-track]');
        if (btn) selectTrack(JSON.parse(btn.dataset.json));
    });

    chip.addEventListener('click', (e) => {
        if (!e.target.closest('#chip-remove')) return;
        chip.classList.add('hidden');
        ['inp-spotify-id', 'inp-title', 'inp-artist', 'inp-cover', 'inp-youtube-id']
            .forEach((id) => {
                hidden(id).value = '';
            });
        input.value = '';
    });
}
