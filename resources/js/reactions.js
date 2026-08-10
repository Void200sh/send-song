// ── REAKSI EMOJI (ala WhatsApp) ──
// - Chip emoji yang sudah ada tampil langsung, klik = toggle reaksi.
// - Tombol "+" membuka picker emoji (posisi fixed biar gak ke-clip kartu), pilih emoji = reaksi.
// - Semua update via POST /messages/{id}/react (JSON), tanpa reload halaman.
const REACTION_EMOJIS = ['👍', '❤️', '😂', '😮', '😢', '🙏']; // sinkron dengan Message::REACTION_EMOJIS

function closeAllPopovers() {
    document.querySelectorAll('[data-react-popover]').forEach((p) => {
        p.classList.add('hidden');
        // Reset aria-expanded tombol "+" pasangannya (biar gak nyangkut "true")
        p.closest('[data-react-picker]')?.querySelector('[data-react-open]')
            ?.setAttribute('aria-expanded', 'false');
    });
}

// Kirim reaksi (toggle) ke server, lalu update chip di bar yang sama
async function sendReaction(bar, emoji, btn) {
    const messageId = bar?.dataset.messageId;
    if (!messageId) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    btn.disabled = true;
    try {
        const res = await fetch(`/messages/${messageId}/react`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ emoji }),
        });
        if (!res.ok) throw new Error('request gagal');
        const data = await res.json();

        updateBar(bar, data);

        // Micro-interaction: animasi "pop" di tombol yang diklik
        btn.animate(
            [
                { transform: 'scale(1)' },
                { transform: 'scale(1.4)' },
                { transform: 'scale(1)' },
            ],
            { duration: 250, easing: 'ease-out' }
        );
    } catch (err) {
        // Gagal jaringan / CSRF / validasi → reload biar state konsisten
        window.location.reload();
    } finally {
        btn.disabled = false;
    }
}

// Update jumlah + tampil/sembunyi + state aktif semua chip di satu bar
function updateBar(bar, data) {
    bar.querySelectorAll('[data-react]').forEach((b) => {
        const em = b.dataset.emoji;
        const count = data.counts[em] ?? 0;

        b.querySelector('[data-react-count]').textContent = count;
        b.classList.toggle('hidden', count === 0); // chip hilang kalau gak ada reaksi lagi

        const active = data.active === em;
        b.dataset.active = active ? '1' : '0';
        b.setAttribute('aria-pressed', active ? 'true' : 'false');
        b.title = active ? 'Batalkan reaksi' : 'Beri reaksi';
        b.classList.toggle('bg-[#171717]', active);
        b.classList.toggle('border-[#171717]', active);
        b.classList.toggle('text-white', active);
        b.classList.toggle('shadow-sm', active);
        b.classList.toggle('bg-white/70', !active);
        b.classList.toggle('border-[#E9E9E9]', !active);
        b.classList.toggle('text-gray-600', !active);
        b.classList.toggle('hover:border-[#171717]', !active);
        b.classList.toggle('hover:text-gray-900', !active);
    });
}

document.addEventListener('click', (e) => {
    // Tombol "+" → buka/tutup picker emoji
    const openBtn = e.target.closest('[data-react-open]');
    if (openBtn) {
        e.preventDefault();
        const popover = openBtn.closest('[data-react-picker]')?.querySelector('[data-react-popover]');
        if (!popover) return;

        const wasOpen = !popover.classList.contains('hidden');
        closeAllPopovers();

        if (!wasOpen) {
            // Posisi fixed tepat di atas tombol "+" — bebas dari overflow-hidden kartu.
            // Di-clamp biar gak keluar layar (kiri/kanan/atas).
            const rect = openBtn.getBoundingClientRect();
            const popWidth = 260;
            const popHeight = 48;
            const desiredBottom = window.innerHeight - rect.top + 8;
            popover.style.position = 'fixed';
            popover.style.left = `${Math.max(8, Math.min(rect.left, window.innerWidth - popWidth))}px`;
            popover.style.bottom = `${Math.max(8, Math.min(window.innerHeight - popHeight, desiredBottom))}px`;
            popover.classList.remove('hidden');
            openBtn.setAttribute('aria-expanded', 'true');
        } else {
            openBtn.setAttribute('aria-expanded', 'false');
        }
        return;
    }

    // Pilih emoji di picker → kirim reaksi, tutup picker
    const option = e.target.closest('[data-react-option]');
    if (option) {
        e.preventDefault();
        const bar = option.closest('[data-reactions]');
        closeAllPopovers();
        if (bar) sendReaction(bar, option.dataset.emoji, option);
        return;
    }

    // Klik chip reaksi yang sudah ada → toggle (tutup picker kalo lagi kebuka)
    const chip = e.target.closest('[data-react]');
    if (chip) {
        e.preventDefault();
        closeAllPopovers();
        sendReaction(chip.closest('[data-reactions]'), chip.dataset.emoji, chip);
        return;
    }

    // Klik di tempat lain → tutup semua picker
    closeAllPopovers();
});

// Picker posisinya fixed — kalau halaman di-scroll, tutup biar gak melayang sendiri
window.addEventListener('scroll', closeAllPopovers, { passive: true });

// Tutup picker pakai tombol Esc
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeAllPopovers();
});
