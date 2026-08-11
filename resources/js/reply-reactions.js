// ── REAKSI BALASAN (ala WhatsApp, per balasan) ──
// - Chip emoji di setiap balasan, klik = toggle reaksi.
// - Update via POST /replies/{id}/react (JSON), tanpa reload halaman.
// - State aktif dihitung per (balasan, emoji) dari reaksi pengunjung ini.
document.addEventListener('click', async (e) => {
    const chip = e.target.closest('[data-react-reply]');
    if (!chip) return;

    e.preventDefault();
    e.stopPropagation();

    const replyId = chip.dataset.reactReply;
    const emoji = chip.dataset.emoji;
    if (!replyId || !emoji) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    chip.disabled = true;
    try {
        const res = await fetch(`/replies/${replyId}/react`, {
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

        // Update semua chip reaksi di bar balasan yang sama
        const bar = chip.closest('[data-reply-reactions]');
        if (bar) updateBar(bar, data);

        // Micro-interaction: animasi "pop"
        chip.animate(
            [
                { transform: 'scale(1)' },
                { transform: 'scale(1.35)' },
                { transform: 'scale(1)' },
            ],
            { duration: 250, easing: 'ease-out' }
        );
    } catch (err) {
        window.location.reload();
    } finally {
        chip.disabled = false;
    }
});

// Update jumlah + tampil/sembunyi + state aktif semua chip di satu bar balasan
function updateBar(bar, data) {
    bar.querySelectorAll('[data-react-reply]').forEach((b) => {
        const em = b.dataset.emoji;
        const count = data.counts[em] ?? 0;

        const countEl = b.querySelector('[data-reply-react-count]');
        if (countEl) countEl.textContent = count;
        b.classList.toggle('hidden', count === 0 && b.dataset.active !== '1');

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
