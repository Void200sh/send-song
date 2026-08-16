// ── KAMERA UNTUK HALAMAN ADMIN ──
// Entry Vite terpisah dari bundle publik: hanya memuat initCamera agar
// form "Tambah Pesan" di panel admin bisa jepret foto seperti form publik.
import { initCamera } from './camera.js';

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCamera);
} else {
    initCamera();
}