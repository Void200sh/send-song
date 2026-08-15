// ── INFINITE SCROLL (halaman browse) ──
// - Sentinel ([data-infinite-sentinel]) dipantau IntersectionObserver.
// - Begitu sentinel kelihatan di viewport (+400px buffer), fetch next page
//   dari [data-infinite-grid] data-next-url → append fragment kartu (JSON .html).
// - Semua interaksi kartu (player/reaksi/share/lapor) pakai event delegation
//   document-level, jadi kartu hasil append otomatis berfungsi.
// - Status (spinner/error/end) dirender di Blade — Tailwind di project ini
//   hanya memindai *.blade.php, jadi class dinamis JS tidak ter-generate.
const grid = document.querySelector('[data-infinite-grid]');
const sentinel = document.querySelector('[data-infinite-sentinel]');

export function initInfiniteScroll() {
    if (!grid || !sentinel) return;

    const status = document.querySelector('[data-infinite-status]');
    const loadingEl = status?.querySelector('[data-infinite-loading]');
    const errorEl = status?.querySelector('[data-infinite-error]');
    const endEl = status?.querySelector('[data-infinite-end]');
    const retryBtn = status?.querySelector('[data-infinite-retry]');

    let nextUrl = grid.dataset.nextUrl || null;
    let hasMore = grid.dataset.hasMore === '1';
    let loading = false;

    const show = (el) => el?.classList.remove('hidden');
    const hide = (el) => el?.classList.add('hidden');

    function finish() {
        hide(loadingEl);
        hide(errorEl);
        show(endEl);
        observer?.disconnect();
        window.removeEventListener('scroll', onScrollFallback);
    }

    async function loadMore() {
        if (loading || !hasMore) return;
        if (!nextUrl) {
            finish();
            return;
        }

        loading = true;
        hide(errorEl);
        show(loadingEl);

        try {
            const res = await fetch(nextUrl, {
                headers: { 'Accept': 'application/json' },
            });
            if (!res.ok) throw new Error('request gagal');

            const data = await res.json();
            grid.insertAdjacentHTML('beforeend', data.html);

            nextUrl = data.next_page_url || null;
            hasMore = !!data.has_more;

            if (!hasMore) {
                finish();
            } else {
                hide(loadingEl);
            }
        } catch (err) {
            hide(loadingEl);
            show(errorEl);
        } finally {
            loading = false;
        }
    }

    retryBtn?.addEventListener('click', loadMore);

    // Fallback scroll (browser tanpa IntersectionObserver)
    function onScrollFallback() {
        const rect = sentinel.getBoundingClientRect();
        if (rect.top - window.innerHeight < 400) loadMore();
    }

    let observer = null;
    if ('IntersectionObserver' in window) {
        observer = new IntersectionObserver(
            (entries) => {
                if (entries.some((e) => e.isIntersecting)) loadMore();
            },
            { rootMargin: '400px 0px' }
        );
        observer.observe(sentinel);
    } else {
        window.addEventListener('scroll', onScrollFallback, { passive: true });
        onScrollFallback();
    }

    // Halaman cuma 1 batch (filter ketat / isi feed habis) → langsung tandai selesai
    if (!hasMore) finish();
}
