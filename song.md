# 📘 SONG.MD — Dokumentasi Lengkap SkanidaSong (Send Song SMK)

> Dokumen master untuk project **SkanidaSong — Send Song SMK**. Berisi penjelasan menyeluruh tentang semua fitur, alur kerja (flow), struktur database, integrasi API eksternal, dan hal-hal teknis lainnya yang dijelaskan secara detail dan runut.

---

## Daftar Isi

1. [Ringkasan Project](#1-ringkasan-project)
2. [Struktur Project & Peran Setiap Bagian](#2-struktur-project)
3. [Semua Fitur](#3-semua-fitur)
4. [Alur Kerja (Flow)](#4-alur-kerja-flow)
5. [Database](#5-database)
6. [Integrasi API Eksternal & Konfigurasi](#6-integrasi-api-eksternal)
7. [Keamanan & Anonimitas](#7-keamanan--anonimitas)
8. [Cara Menjalankan, Test & Build](#8-cara-menjalankan-test--build)
9. [Daftar Route](#9-daftar-route)
10. [Catatan Migration & Changelog](#10-catatan-migration--changelog)

---

## 1. Ringkasan Project

| Item | Keterangan |
| --- | --- |
| **Nama Produk** | Skan Song — Send Song SMK (dikenal juga sebagai **Skan Song SMK**) |
| **Jenis** | Platform web anonim (menfess) untuk siswa |
| **Konsep** | Siswa mengirimkan pesan rahasia yang ditujukan ke seseorang berdasarkan **nama + kelas**, dilengkapi **satu lagu** yang didedikasikan |
| **Slot Teknologi** | Laravel 12.62 + PHP 8.2+, Blade Template, Tailwind CSS (v3, via PostCSS), Vite, JavaScript vanilla (ES module) |
| **Database** | SQLite (`database/database.sqlite`) |
| **Template Admin** | Meridian / Stisla (static CSS+JS di `public/assets`) |
| **Unauthenticated** | Pengirim TIDAK perlu login (anonim). Admin login via Laravel Breeze auth |
| **Fitur Kunci** | Cari lagu (iTunes) → pilih → resolve ke YouTube → klip custom (maks 30 detik) → pesan tampil di feed dengan player custom |

> **Catatan branding:** Nama brand yang dipakai di halaman publik adalah **"Skan Song SMK"**, sementara di panel admin tampil **"Skanida Songs"**. Keduanya merujuk produk yang sama.

---

## 2. Struktur Project

```
send-song/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── Controller.php             (base controller)
│   │       ├── MessageController.php      ★ otak fitur pesan publik (index, show, store)
│   │       ├── AdminController.php        ★ otak panel admin (dashboard, pesan, lagu, kelas, export)
│   │       └── SongController.php         ★ API internal pencarian & resolve lagu
│   │   └── (Auth/*)                        (bawaan Laravel Breeze)
│   ├── Models/
│   │   ├── Message.php                    ★ model tabel messages
│   │   └── User.php                       (user admin / akun)
│   └── Services/
│       ├── iTunesService.php             ★ cari lagu lewat iTunes Search API (ada preview 30 dtk)
│       ├── YouTubeService.php            ★ resolve lagu → ID video YouTube (API + fallback scrape)
│       └── SpotifyService.php            (token + search Spotify — dikonfigurasi, belum dipakai di flow aktif)
├── routes/
│   ├── web.php                           ★ SEMUA RUTE PUBLIK + ADMIN di sini
│   └── auth.php                          (breeze)
├── database/
│   ├── migrations/                       (6 migration — lihat Bab 5)
│   ├── seeders/AdminSeeder.php           ★ seeder admin (admin@skanida.com)
│   └── database.sqlite                   (file database)
├── resources/
│   ├── css/app.css                       ★ Tailwind + animasi marquee
│   ├── js/
│   │   ├── app.js                        (bootstrapping)
│   │   ├── song-search.js                ★ logika cari lagu, klip, waveform, review (form story)
│   │   ├── player.js                     ★ custom player YouTube via iframe tersembunyi
│   │   └── bootstrap.js
│   └── views/
│       ├── welcome.blade.php               ★ landing page (stats + marquee)
│       ├── story.blade.php               ★ halaman kirim story (form)
│       ├── messages/
│       │   ├── index.blade.php           ★ browse feed (grid + filter + pagination)
│       │   └── show.blade.php            ★ detail satu pesan
│       ├── dashboard.blade.php           (breeze)
│       ├── admin/                        ★ panel admin (layout + 5 halaman)
│       ├── auth/, profile/, components/, layouts/ (breeze)
│       └── vendor/pagination/stisla.blade.php  ★ tampilan pagination custom
├── public/
│   ├── assets/                           ★ template Stisla/Meridian (CSS/JS static)
│   └── favicon.* / robots.txt
├── config/, bootstrap/, storage/, tests/, vendor/, node_modules/
└── artisan, package.json, composer.json, vite.config.js, tailwind.config.js, postcss.config.js
```

> **Pola: Kode publik (pengirim/penerima) tidak butuh auth.** `routes/auth.php` hanya digunakan untuk admin & akun.

---

## 3. Semua Fitur

### 3.1 Halaman Utama / Landing (`GET /`)

- **Tujuan:** Halaman sambutan + statistik.
- **Stats Cards** (3 kartu):
  - total pesan (semua record),
  - total kelas unik yang pernah dikirimi pesan,
  - waktu pesan terakhir (`diffForHumans` / strip `-` bila kosong).
- **2 Marquee** (teks animasi):
  - Marquee 1 → `to: [nama] — [kelas]` berjalan **kanan→kiri** (`animate-marquee`).
  - Marquee 2 → isi pesan (dipotong 60 karakter) berjalan **kiri→kanan** (`animate-marquee-reverse`).
  - Konten diambil acak (`inRandomOrder()->limit(20)`) lalu digandakan 2× di DOM agar loop mulus.
- **CTA**: tombol "tell your story" (ke `/story`) & "lihat semua" (ke `/messages`).
- **Data dikirim dari route closure** (bukan controller) → 4 variabel: `totalMessages`, `totalKelas`, `latestMessage`, `marqueeMessages`.

### 3.2 Kirim Story / Form (`GET /story`, `POST /messages`)

Halaman `story.blade.php` berisi satu form lengkap:

| Field | Tipe | Aturan | Keterangan |
| --- | --- | --- | --- |
| `sender_name` | text | optional, max 255 | Nama pengirim; kosong/white-space → **anonim** (disimpan `NULL`) |
| `recipient_name` | text | **required**, max 255 | Nama penerima |
| `kelas` | hidden + dropdown custom | **required** | 33 opsi kelas (X/XI/XII; jurusan PPLG, PM, AKL, MPLB) |
| `message` | textarea | **required** | Isi pesan |
| lagu (cari+pilih) | — | diwajibkan via UI | Pencarian lagu → pilih → `song_title`, `song_artist`, `cover_url`, `youtube_video_id` (resolve), `spotify_track_id` |
| `clip_start_seconds` / `clip_end_seconds` | hidden | opsional | Klip custom (maks 30 dtk). Bila tidak valid → full lagu (`NULL`) |

**Alur interaksi form (semua di `song-search.js`):**

1. **Cari lagu** — saat user mengetik di input `#song-search`:
   - debounce 400 ms → `GET /api/songs/search?q=...`
   - hasil dari **iTunes Search API** (8 track): cover, judul, artis, durasi, preview ✅.
   - list hasil tampil sebagai dropdown.
2. **Preview** — tiap baris hasil punya tombol ▶ yang memutar **preview iTunes (~30 dtk)**.
3. **Pilih lagu** — klik baris → lagu di-pick, hidden inputs diisi (`spotify_id`, `title`, `artist`, `cover`), lalu otomatis memanggil `POST /api/songs/resolve { title, artist }`:
   - backend `SongController@resolve` → `YouTubeService::searchAudio()` → dapat `youtube_id` (atau `null` bila tidak ada).
   - status chip: "mencari audio..." → "✓ audio siap" atau "audio tidak ditemukan (fallback)".
4. **Durasi & klip** — setelah lagu dipilih muncul panel durasi:
   - toggle **full lagu** / **klip custom**.
   - dalam mode klip: **waveform** (kombinasi bar asli preview + pseudo-bar), handle kiri/kanan, playhead tengah.
   - maks klip = **30 detik**; drag kotak untuk menggeser; tap untuk seek.
   - tombol review ▶ memutar video YouTube tersembunyi dari awal klip (loop 30 dtk / full).
5. **Submit** — tombol submit **baru aktif setelah ada lagu terpilih** (butuh `song_title`/`spotify_id`).

### 3.3 Browse / Feed Pesan (`GET /messages`)

- **Grid responsif**: 1 kolom (HP) → 2 (tablet) → 3 (desktop).
- **Search by name** — `?search=...` → filter `recipient_name LIKE %...%`.
- **Filter kelas** — `?kelas=...` → dropdown custom (berisi 33 opsi kelas) + tombol **search** & **reset**.
- **Pagination** — 12 pesan per halaman (`paginate(12)`), dengan `->appends(request()->query())` agar filter tidak hilang saat pindah halaman. Tampilan pagination custom `stisla.blade.php`.
- **Kartu pesan**:
  - `from: [sender_name]` atau `anonim` (font Reenie Beanie),
  - `to: [recipient_name]`,
  - kelas • waktu (`diffForHumans`),
  - isi pesan (potong 80 karakter),
  - **player** bila `youtube_video_id` (lihat 3.5), atau tombol "buka lagu di Spotify" bila hanya ada `spotify_track_id`.
- Klik kartu → buka halaman detail (`/messages/{id}`) — kecuali klik pada elemen interaktif (play, seekbar, tombol) dicegah navigasi via `player.js`.

### 3.4 Detail Pesan (`GET /messages/{id}`)

- Memperlihatkan satu kartu pesan secara penuh: from, to, kelas, waktu (relatif `diffForHumans` + absolut `format('d M Y, H:i')`), dan isi pesan lengkap tanpa potongan.
- Player tampil dengan ukuran lebih besar.
- Tombol "back to browse" untuk kembali ke feed.

### 3.5 Lagu / Player Custom (di browser)

`player.js` menyalakan **YouTube IFrame API** di dalam iframe tersembunyi (`#yt-audio-host`, 1×1px, opacity 0, off-screen) sehingga UI player bisa dikustomisasi sepenuhnya:

- **Mode Full** — video full durasi.
- **Mode Klip** — begitu `currentTime >= clip.end` → `seekTo(clip.start)` (loop klip).
- **UI**: tombol play/pause, seekbar (drag), time current/durasi, cover + judul + artis.
- **Fallback**:
  - `youtube_video_id` tidak ada → tombol link buka Spotify.
  - Video YouTube error/dihapus (`onError`) → tampil link "buka di Spotify".
- Player dalam kartu & detail menggunakan instance yang sama (hanya satu lagu yang diputar pada satu waktu).
- **Review (halaman story)** — saat mode "full"/"klip", preview memakai player YouTube tersembunyi yang sama (fungsi `window.storyReview.*`); jika YouTube gagal, audio preview iTunes dipakai sebagai fallback.

### 3.6 Pencarian & Resolve Lagu (API Internal)

**Endpoint:**

| Endpoint | Method | Fungsi |
| --- | --- | --- |
| `/api/songs/search?q=...` | GET | Cari lagu di **iTunes** (dengan preview) — min 2 karakter |
| `/api/songs/resolve` | POST | Kirim `{title, artist}` → dapatkan `youtube_id` |

**Alur resolve (`YouTubeService@searchAudio`) — dua lapis:**

1. `config('services.youtube.api_key')` terisi **→ coba YouTube Data API v3** (`search`), 2 variasi query:
   - `"{artist}" "{title}" official audio`
   - `"{title}" "{artist}" audio`
   - hasil diskoring (lihat `pickBest`): kecocokan nama artis +3, judul +2, judul mengandung "official audio" +2, channel artis +1; pinalti -5 bila judul mengandung remix/reaction/cover/karaoke/instrumental/live set. Pilih skor tertinggi.
   - Jika API gagal / tidak ada match → lanjut ke fallback.
2. **Fallback scrape** tanpa API: fetch `https://www.youtube.com/results?search_query=...`, ekstrak JSON `ytInitialData` dari HTML, ambil daftar `videoRenderer`, normalisasi menjadi bentuk mirip output API v3, lalu jalankan `pickBest()` yang sama.

**Verifikasi durasi:** jika API key ada, cek durasi video ≤ 15 menit (`isReasonableDuration`, menggunakan `contentDetails.duration` ISO8601). Jika tidak ada API key, durasi dianggap wajar.

**Cache:** iTunes search di-cache 1 jam (`Cache::remember(itunes_search_<md5(query)>, 3600)`).

### 3.7 Panel Admin (`/admin`) — autentikasi dikelola via Breeze

Sidebar 6 menu:

1. **Dashboard** — ringkasan: total pesan, pesan hari ini, pengirim beridentitas (isi `sender_name`), kelas unik; **grafik area** 14 hari (ApexCharts CDN), top-5 kelas, top-5 lagu, statistik lagu (dengan / tanpa lagu, lagu unik, artis unik), dan 5 pesan terbaru (tabel).
2. **Pesan Masuk** — tabel semua pesan (`sender_name` / Anonim, penerima, kelas, isi, kolom lagu, waktu); search `?search=` (cocok sender ATAU recipient), filter `?kelas=`; aksi: **hapus** + **resolve lagu** perpesan (mengisi `youtube_video_id` yang hilang untuk pesan lama).
3. **Lagu** — perpustakaan lagu unik (group by judul+artis+cover): cover, judul, artis, berapa kali dikirim, terakhir dipakai, tombol putar (YouTube / Spotify sample), search & filter kelas, pagination.
4. **Kelas** — rekap tiap kelas: total pesan, pesan membawa lagu, lagu unik, pesan pertama & terakhir, link ke daftar pesan kelas tersebut.
5. **Export Data** — download CSV:
   - **Export Pesan** → kolom ID, Dari, Untuk, Kelas, Pesan, Judul Lagu, Artis, Spotify ID, YouTube ID, Waktu; filter rentang tanggal (`?from=YYYY-MM-DD&to=YYYY-MM-DD`).
   - **Export Lagu** → judul, artis, berapa kali dikirim, terakhir dikirim.
   - Output CSV dengan **BOM UTF-8** (agar terbuka benar di Excel), nama file `pesan-<from>_sd_<to>.csv` / `lagu-...csv`.
6. **Lihat Website** — link eksternal ke `/`.

**Tambahan tampilan:** toggle **tema gelap/terang** (tersimpan di `localStorage`, berlaku instan via `theme.js`), avatar, logout.

### 3.8 Autentikasi (Laravel Breeze)

- Register / Login / Logout / Forgot & Reset Password / Verify Email.
- Admin: di-seed oleh `AdminSeeder` → **email:** `admin@skanida.com` / **pass:** `skanida1968`.
- Tidak ada role khusus di tabel `users` — panel admin hanya dibatasi middleware `auth`. Akun admin diinisialisasi lewat `AdminSeeder`; pertimbangkan pengecekan email/role untuk produksi.

---

## 4. Alur Kerja (Flow)

### 4.1 Flow Kirim Pesan (end-to-end)

```
1  User buka GET /story
2  Form diisi: nama penerima + kelas + pesan
3  Cari Lagu:
   └> input "song-search" (min 2 huruf)
       → GET /api/songs/search?q=...  (iTunes, cache 1 jam)
       → dropdown hasil + tombol preview
       → klik pilih → chip + hidden fields: spotify_id, title, artist, cover
       → POST /api/songs/resolve { title, artist }
           → YouTubeService::searchAudio (API→fallback scrape)
           → hidden fields: youtube_video_id
4  Atur durasi: full lagu / klip (drag handle, tap seek, playhead, max 30 dtk)
5  Submit → POST /messages (dengan CSRF)
       → MessageController@store
           a. Validasi kolom (required: recipient_name, kelas, message;
              opsional: sender_name, lagu, dan clip)
           b. normalisasi sender: yang kosong jadi NULL (anonim)
           c. ekstraktor spotify: jika ada spotify_url + belum ada spotify_track_id
              → regex extract dari URL Spotify
           d. klip valid hanya jika end > start (maks 600); dan tidak valid → null (full)
           e. Message::create($validated)
       → redirect ke /messages + flash "Pesan berhasil dikirim!"
```

### 4.2 Alur Pencarian & Resolve Lagu (backend)

```
Search           → iTunesService::searchTracks()
                   → Query string: q (>=2), media=music, entity=song, limit 8
Resolver        → YouTubeService::searchAudio(title, artist)
                   1) YouTube Data API v3 (jika ada key) → pickBest (score)
                   2) scrape youtube.com/results (ytInitialData) → pickBest
                   3) durasi <= 900s (jika key ada)
Return { youtube_id, title } ke frontend
```

### 4.3 Alur Player

```
Klik tombol ▶ di kartu
  → load YouTube API iframe (hidden)
  → createPlayer / loadVideoById
  → jika mode klip: seekTo(clip.start); loop balik saat >= clip.end
  → tampilkan kemajuan, durasi, dan waktu di UI
  → jika error (video dihapus) → tampil tautan "buka di Spotify"
Klik kartu (bukan tombol/seekbar) → navigasi ke /messages/{id}
```

### 4.4 Alur Admin Statistik

`AdminController@dashboard`:
- ambil count untuk tiap stat,
- **unique songs/artists** dihitung di PHP (agar jalan di MySQL & SQLite),
- **top songs** (judul + artis dikelompokkan, total frekuensi dikirim),
- **grafik 14 hari** — dibuat list tanggal, setiap hari yang kosong diisi 0
- **top kelas**, **pesan terbaru** (5).

`AdminController@exportMessagesCsv` & `@exportSongsCsv` — query → render row → `fputcsv` → response `text/csv` dengan BOM.

---

## 5. Database

Database: **SQLite** (`database/database.sqlite`). Tabel dibuat via migration:

### 5.1 Tabel `messages` — tabel inti pesan

| Kolom | Tipe | Atribut | Deskripsi |
| --- | --- | --- | --- |
| `id` | BigInt (auto-inc PK) | primary | ID unik |
| `sender_name` | String | nullable | nama pengirim (null = anonim) |
| `recipient_name` | String | not null | nama penerima |
| `kelas` | String | not null | contoh "XI PPLG 1" |
| `message` | Text | not null | isi pesan |
| `spotify_track_id` | String | nullable | ID track Spotify (regex dari URL lama) |
| `song_title` | String | nullable | judul lagu (dari pencarian lagu) |
| `song_artist` | String | nullable | artis/penyanyi |
| `cover_url` | String | nullable | URL cover album (iTunes ~300px) |
| `youtube_video_id` | String | nullable | ID video YouTube hasil resolve (dipakai player) |
| `clip_start_seconds` | UnsignedInt | nullable | start klip (detik); null = full |
| `clip_end_seconds` | UnsignedInt | nullable | end klip (maks 30 dtk); null = full |
| `created_at` | Timestamp | nullable | otomatis Laravel |
| `updated_at` | Timestamp | nullable | otomatis Laravel |

### 5.2 Tabel lain (breeze & framework)

- `users` — akun (id, name, email unique, password, email_verified_at, remember_token, timestamps).
- `password_reset_tokens` — email PK, token, created_at.
- `sessions` — session database driver.
- `cache` (driver database) + `cache_locks`.
- `jobs` + `job_batches` + `failed_jobs` (queue database).

### 5.3 Daftar Migration

```
0001_01_01_000000_create_users_table.php
0001_01_01_000001_create_cache_table.php
0001_01_01_000002_create_jobs_table.php
2025_01_01_000001_create_messages_table.php
2025_01_01_000002_add_sender_name_to_messages_table.php
2026_08_05_000001_add_song_columns_to_messages_table.php     (+ song_title, song_artist, cover_url, youtube_video_id)
2026_08_06_000001_add_duration_seconds_to_messages_table.php
2026_08_06_000002_replace_duration_seconds_with_clip_seconds.php   (ganti duration → clip_start/clip_end; data lama dimigrasi)
```

### 5.4 Daftar Kelas (33 opsi)

`X/XI/XII` masing-masing:
- PPLG 1, 2, 3
- PM 1, 2
- AKL 1, 2, 3
- MPLB 1, 2, 3

---

## 6. Integrasi API Eksternal

| Service | Penggunaan | Konfigurasi `.env` | Catatan |
| --- | --- | --- | --- |
| **iTunes Search API** | Cari lagu (hasilnya punya preview ~30 dtk & metadata cover) | — (tanpa token) | cache 1 jam; limit 8; artwork 100→300 (via `biggerArtwork()`) |
| **YouTube Data API v3** | Resolve lagu→video (prioritas) | `YOUTUBE_API_KEY` | tipe video, category 10, max 5, durasi<=15 menit |
| **YouTube (scrape fallback)** | Resolve tanpa API key | — | parse `ytInitialData` dari `/results`, tanpa key |
| **Spotify** | Token client_credentials + search | `SPOTIFY_CLIENT_ID`, `SPOTIFY_CLIENT_SECRET` | Terdapat `SpotifyService` tapi **tidak dipakai** pada flow aktif (kolom `spotify_track_id` hanya dari pregmatch terhadap URL lama). Token di-cache |

**Penjelasan bidang `spotify_track_id`:** Di versi lama, pengirim paste URL Spotify lalu backend mengekstrak ID. Versi saat ini mengganti alur (iTunes + YouTube), tetapi kolom + fallback tetap untuk kompatibilitas data lama (button "buka di Spotify").

---

## 7. Keamanan & Anonimitas

- **Anonimitas wajib**: data pengirim tidak disimpan (tidak ada kolom IP, user-agent, session) di tabel `messages`. `sender_name` bersifat opsional bagi pengunjung & diubah ke NULL bila kosong.
- **Session** aktif digunakan untuk flash message + auth, tapi tidak dilaporkan ke publik.
- **CSRF**: semua form memakai `@csrf`; API internal ada `X-CSRF-TOKEN`.
- **Validasi**: `$request->validate()` di `store()` & `resolve()`.
- **Pesan** di-render lewat Blade ({{ }}) → escaping HTML otomatis.
- **Admin** butuh login (`middleware('auth')`). Route `/dashboard` (Breeze) juga menerapkan `verified`.

---

## 8. Cara Menjalankan, Test & Build

```bash
# 1. Install dependencies
composer install
npm install

# 2. Environment (jika belum ada)
cp .env.example .env   # Windows: copy .env.example .env
php artisan key:generate

# 3. Database + seeder
touch database/database.sqlite
php artisan migrate --seed      # → membuat admin@skanida.com

# 4. Compile aset frontend
npm run build        # build produksi
# atau
npm run dev          # dev server HMR

# 5. Jalankan server Laravel
php artisan serve    # url default http://localhost:8000

# 6. Test otomatis
composer test        # atau: php artisan test
```

**Credential admin contoh:** `admin@skanida.com` / `skanida1968`.

---

## 9. Daftar Route

| Method | URI | Controller@method | Nama route | Akses |
| --- | --- | --- | --- | --- |
| GET | `/` | closure | — | publik |
| GET | `/messages` | MessageController@index | `messages.index` | publik |
| GET | `/messages/{message}` | MessageController@show | `messages.show` | publik |
| POST | `/messages` | MessageController@store | `messages.store` | publik |
| GET | `/story` | closure→view story | `story.create` | publik |
| GET | `/api/songs/search` | SongController@search | `api.songs.search` | internal |
| POST | `/api/songs/resolve` | SongController@resolve | `api.songs.resolve` | internal |
| GET | `/admin` | AdminController@dashboard | `admin.dashboard` | auth |
| GET | `/admin/messages` | AdminController@messages | `admin.messages` | auth |
| DEL | `/admin/messages/{message}` | AdminController@destroy | `admin.messages.destroy` | auth |
| POST | `/admin/messages/{message}/resolve-song` | AdminController@resolveSong | `admin.messages.resolve-song` | auth |
| GET | `/admin/songs` | AdminController@songs | `admin.songs` | auth |
| GET | `/admin/kelas` | AdminController@kelas | `admin.kelas` | auth |
| GET | `/admin/export` | AdminController@export | `admin.export` | auth |
| GET | `/admin/export/messages.csv` | AdminController@exportMessagesCsv | `admin.export.messages` | auth |
| GET | `/admin/export/songs.csv` | AdminController@exportSongsCsv | `admin.export.songs` | auth |
| GET/POST/DEL | `/logout`, `/admin/profile`, dll | (breeze) | — | auth |
| GET/POST | `/login`, `/register`, `/forgot-password`, `/reset-password` | (breeze) | — | guest |

---

## 10. Catatan Migration & Changelog

### Changelog singkat

| Tanggal | Perubahan |
| --- | --- |
| 2025-01 | Tabel `messages` awal (recipient, kelas, message, spotify_track_id). Tambah `sender_name`. |
| 2026-08-05 | Tambah kolom lagu: `song_title`, `song_artist`, `cover_url`, `youtube_video_id`. |
| 2026-08-06 | Tambah `duration_seconds`, lalu diganti `clip_start_seconds`/`clip_end_seconds` (data lama dipetakan 0→duration). |
| Terkini | Implementasi alur cari-lagu iTunes + resolve YouTube + klip custom + panel admin lengkap (dashboard/grafik/export) + tema admin gelap-terang. |

### Hal yang perlu diperhatikan developer baru

1. Tombol submit form story terkunci minimal 1 lagu dipilih — jika Anda menambahkan mode "tanpa lagu", ubah `updateSubmitEnabled()` di `song-search.js`.
2. Preview panjang di iTunes hanya 30 detik; Waveform bar asli diisi dari audio preview yang dipadukan pseudo-bar.
3. Klip maksimum di-hardset **30 detik** (`song-search.js`) dan divalidasi ulang di `store()` via rule min/max (600). Jika ingin ubah batas, ubah di kedua tempat.
4. `public/assets` berisi statis Stisla/Meridian; jika update template perlu membangun ulang aset.
5. Data lagu otomatis tersimpan setiap pengiriman (`song_title/artist/cover/youtube_id`). Untuk pesan lama tanpa `youtube_video_id`, admin dapat menekan tombol **resolve** di halaman Pesan Masuk.
6. Kolom `spotify_track_id` masih dipakai untuk fallback "buka di Spotify" bila video YouTube tidak tersedia.
7. Saat menjalankan migration baru, cadangkan terlebih dulu `database/database.sqlite`.

---

*Dokumen ini ditulis otomatis berdasarkan kode sumber project dan diperbarui mengikuti setiap perubahan besar.*
*Terakhir diperbarui: 2026 - [sesuaikan]*