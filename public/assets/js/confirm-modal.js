// ── CONFIRM MODAL: pengganti window.confirm() yang di-style (dipakai halaman admin) ──
// - Form dengan atribut data-confirm="pesan" → saat submit, tampilkan modal konfirmasi.
// - Klik "ya" → form di-submit; klik "batal"/Esc/klik luar → batal.
// - Data atribut opsional: data-confirm-ok="teks tombol" (default "ya"), data-confirm-title="judul".
// - Style inline penuh (admin tidak pakai Tailwind di public pages).

(function () {
    var modal = null;

    function buildModal() {
        modal = document.createElement('div');
        modal.style.cssText =
            'position:fixed;inset:0;z-index:9998;display:flex;align-items:center;justify-content:center;' +
            'padding:20px;background:rgba(23,23,23,.45);opacity:0;transition:opacity .18s ease;';

        var card = document.createElement('div');
        card.setAttribute('role', 'alertdialog');
        card.setAttribute('aria-modal', 'true');
        card.style.cssText =
            'width:100%;max-width:360px;background:#fff;border:1px solid #E9E9E9;border-radius:20px;' +
            'padding:24px;text-align:center;box-shadow:0 20px 50px rgba(0,0,0,.18);' +
            'transform:scale(.95);transition:transform .18s ease;font-family:inherit;';

        card.innerHTML =
            '<div id="confirm-modal-icon" style="width:52px;height:52px;margin:0 auto;border-radius:50%;' +
            'background:#FEF2F2;border:1px solid #FECACA;display:flex;align-items:center;justify-content:center;">' +
            '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="1.8" ' +
            'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
            '<path d="M12 9v4m0 4h.01M10.3 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.7 3.86a2 2 0 0 0-3.4 0z"/></svg>' +
            '</div>' +
            '<h3 id="confirm-modal-title" style="margin:14px 0 6px;font-size:18px;font-weight:700;color:#171717;">Konfirmasi</h3>' +
            '<p id="confirm-modal-message" style="margin:0 0 20px;font-size:13.5px;line-height:1.55;color:#6B7280;"></p>' +
            '<div style="display:flex;gap:10px;">' +
            '<button type="button" id="confirm-modal-cancel" style="flex:1;padding:11px 0;border-radius:12px;' +
            'border:1px solid #D9D9D9;background:#fff;color:#4B5563;font-size:14px;font-weight:600;cursor:pointer;' +
            'transition:border-color .15s ease,color .15s ease;">batal</button>' +
            '<button type="button" id="confirm-modal-ok" style="flex:1;padding:11px 0;border-radius:12px;' +
            'border:1px solid #DC2626;background:#DC2626;color:#fff;font-size:14px;font-weight:600;cursor:pointer;' +
            'transition:background .15s ease;">ya</button>' +
            '</div>';

        modal.appendChild(card);
        document.body.appendChild(modal);
        return modal;
    }

    // Tampilkan modal, balas Promise<boolean>
    function askConfirm(message, okText, title) {
        if (!modal) modal = buildModal();

        var card = modal.firstElementChild;
        var ok = card.querySelector('#confirm-modal-ok');
        var cancel = card.querySelector('#confirm-modal-cancel');
        var titleEl = card.querySelector('#confirm-modal-title');
        var msgEl = card.querySelector('#confirm-modal-message');
        var previouslyFocused = document.activeElement;

        titleEl.textContent = title || 'Konfirmasi';
        msgEl.textContent = message;
        ok.textContent = okText || 'ya';

        return new Promise(function (resolve) {
            var settled = false;

            function close(result) {
                if (settled) return;
                settled = true;
                modal.style.opacity = '0';
                card.style.transform = 'scale(.95)';
                document.removeEventListener('keydown', onKeydown, true);
                modal.removeEventListener('click', onOverlay);
                ok.removeEventListener('click', onOk);
                cancel.removeEventListener('click', onCancel);
                setTimeout(function () {
                    modal.style.display = 'none';
                    if (previouslyFocused && previouslyFocused.focus) previouslyFocused.focus();
                }, 180);
                resolve(result);
            }

            function onKeydown(e) {
                if (e.key === 'Escape') { e.preventDefault(); close(false); }
            }
            function onOverlay(e) {
                if (e.target === modal) close(false);
            }
            function onOk() { close(true); }
            function onCancel() { close(false); }

            document.addEventListener('keydown', onKeydown, true);
            modal.addEventListener('click', onOverlay);
            ok.addEventListener('click', onOk);
            cancel.addEventListener('click', onCancel);

            modal.style.display = 'flex';
            requestAnimationFrame(function () {
                modal.style.opacity = '1';
                card.style.transform = 'scale(1)';
            });
            cancel.focus();
        });
    }

    // Intercept semua form dengan data-confirm
    document.addEventListener('submit', function (e) {
        var form = e.target;
        var message = form.getAttribute('data-confirm');
        if (!message) return;

        e.preventDefault();
        e.stopPropagation();

        askConfirm(
            message,
            form.getAttribute('data-confirm-ok') || undefined,
            form.getAttribute('data-confirm-title') || undefined
        ).then(function (ok) {
            if (ok) form.submit(); // submit langsung, lewati event submit (hindari loop)
        });
    });

    // Ekspos global biar bisa dipakai dari atribut onclick juga kalau perlu
    window.styledConfirm = askConfirm;
})();
