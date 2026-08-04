# INTERNALS.md — Send Song SMK

> Dokumentasi jeroan project. Di sini gw catet arsitektur, alur data, penjelasan tiap file, dan changelog perubahan.

---

## Daftar Isi

1. [Struktur Folder](#1-struktur-folder)
2. [Arus Data (Data Flow)](#2-arus-data-data-flow)
3. [Penjelasan Per-File](#3-penjelasan-per-file)
4. [Regex Spotify Extraction](#4-regex-spotify-extraction)
5. [Changelog / Catatan Perubahan](#5-changelog--catatan-perubahan)
6. [Tips Buat Lo](#6-tips-buat-lo)

---

## 1. Struktur Folder

```
C:\laragon\www\send-song-smk\
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Controller.php          (base controller bawaan Laravel)
│   │   │   └── MessageController.php   ★ otak utama fitur messages
│   ├── Models/
│   │   ├── Message.php                 ★ model buat tabel messages
│   │   └── User.php                    (default, gak dipake)
│   └── Providers/                      (bawaan Laravel, gak disentuh)
├── database/
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php   (default)
│   │   ├── 0001_01_01_000001_create_cache_table.php   (default)
│   │   ├── 0001_01_01_000002_create_jobs_table.php    (default)
│   │   └── 2025_01_01_000001_create_messages_table.php ★ migration tabel messages
│   └── seeders/                        (kosong)
├── resources/
│   ├── css/
│   │   └── app.css                     ★ Tailwind + custom animations
│   ├── js/
│   │   ├── app.js                      (cuma import bootstrap)
│   │   └── bootstrap.js                (bawaan Laravel)
│   └── views/
│       ├── messages/
│       │   └── index.blade.php         ★ halaman browse / feed pesan
│       └── welcome.blade.php           ★ halaman utama (landing + form kirim)
├── routes/
│   ├── console.php                     (gak dipake)
│   └── web.php                         ★ ★ SEMUA RUTE ADA DI SINI
├── config/                             (konfigurasi Laravel standar)
├── public/                             (entry point Laravel)
├── vendor/                             (dependencies composer)
├── composer.json
├── package.json
├── vite.config.js
├── PRD.md                              (dokumen requirement)
└── INTERNALS.md                        (file ini)
```

> **Catatan:** Project ini pake Laravel 11/12 + Tailwind CSS v4 (via Vite). Database pake SQLite (`database/database.sqlite`).

---

## 2. Arus Data (Data Flow)

### Alur 1: User Kirim Pesan (Store)

```
┌──────────────┐     POST /messages     ┌──────────────────┐
│  welcome     │ ──────────────────────> │  MessageController│
│  .blade.php  │   (form submit)        │  → store()       │
│  (form)      │                        └────────┬─────────┘
└──────────────┘                                 │
                                                  ▼
                                         ┌──────────────────┐
                                         │  1. Validasi      │
                                         │     (required:    │
                                         │   recipient_name, │
                                         │   kelas, message) │
                                         │     (nullable:    │
                                         │   spotify_url)    │
                                         └────────┬─────────┘
                                                  ▼
                                         ┌──────────────────┐
                                         │  2. Extract       │
                                         │     Spotify ID    │
                                         │     dari URL      │
                                         │     (regex)       │
                                         └────────┬─────────┘
                                                  ▼
                                         ┌──────────────────┐
                                         │  3. Simpan ke DB  │
                                         │     Message::     │
                                         │     create()      │
                                         └────────┬─────────┘
                                                  ▼
                                         ┌──────────────────┐
                                         │  4. Redirect ke   │
                                         │     /messages     │
                                         │     + session     │
                                         │     success       │
                                         └──────────────────┘
```

### Alur 2: User Lihat Feed (Browse)

```
┌──────────────┐  GET /messages          ┌──────────────────┐
│  browser     │  ?search=xxx&kelas=yyy  │  MessageController│
│  / user      │ ──────────────────────> │  → index()        │
└──────────────┘                         └────────┬─────────┘
                                                  ▼
                                         ┌──────────────────┐
                                         │  1. Query builder │
                                         │     → filter nama │
                                         │     → filter kelas│
                                         │     → latest()    │
                                         │     → paginate(10)│
                                         └────────┬─────────┘
                                                  ▼
                                         ┌──────────────────┐
                                         │  2. Pass data ke  │
                                         │     view:         │
                                         │   - $messages     │
                                         │   - $kelasList    │
                                         │   - $selectedKelas│
                                         │   - $search       │
                                         └────────┬─────────┘
                                                  ▼
                                         ┌──────────────────┐
                                         │  3. Render blade  │
                                         │     messages/     │
                                         │     index.blade   │
                                         │     .php          │
                                         └──────────────────┘
                                                  ▼
                                         ┌──────────────────┐
                                         │  Tampil card:     │
                                         │  - Nama penerima  │
                                         │  - Kelas + waktu  │
                                         │  - Isi pesan      │
                                         │  - Spotify embed  │
                                         │    (iframe 80px)  │
                                         └──────────────────┘
```

### Alur 3: Halaman Utama (Landing)

```
┌──────────────┐  GET /                  ┌──────────────────┐
│  browser     │ ──────────────────────> │  routes/web.php   │
│  / user      │                         │  (closure, bukan  │
└──────────────┘                         │   controller)     │
                                         └────────┬─────────┘
                                                  ▼
                                         ┌──────────────────┐
                                         │  Query:           │
                                         │  - total messages │
                                         │  - total kelas    │
                                         │  - latest message │
                                         │  - 20 random msg  │
                                         │    (buat marquee) │
                                         └────────┬─────────┘
                                                  ▼
                                         ┌──────────────────┐
                                         │  Render welcome   │
                                         │  .blade.php       │
                                         └──────────────────┘
```

---

## 3. Penjelasan Per-File

### 3.1 `routes/web.php` — Daftar Semua Rute

```php
// Halaman landing (welcome page)
Route::get('/', function () { ... });

// Halaman browse (GET — liat pesan)
Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');

// Kirim pesan baru (POST)
Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
```

**Total cuma 3 rute.** Gak ada auth, gak ada middleware, gak ada grouping. Simpel.

> Rute `/` pake **closure** (bukan controller) karena cuma ngambil beberapa data statistik buat ditampilkan di landing. Kalo udah mulai kompleks, bisa dipindahin ke controller sendiri.

---

### 3.2 `app/Http/Controllers/MessageController.php` — Otak Fitur Messages

#### Method `index(Request $request)`
- Inisialisasi `$kelasList`: array hardcode 24 kelas (X, XI, XII masing-masing 4 jurusan × 2 rombel).
- Filter pencarian: kalo ada `?search=...`, tambah WHERE `recipient_name LIKE %...%`.
- Filter kelas: kalo ada `?kelas=...`, tambah WHERE `kelas = ...`.
- Ambil data: `$query->latest()->paginate(10)` — 10 pesan per halaman, terbaru di atas.
- Return view dengan compact 4 variable.

#### Method `store(Request $request)`
- Validasi pake `$request->validate(...)`.
- Kalo `spotify_url` diisi, panggil `$this->extractSpotifyTrackId()` buat ambil ID-nya.
- Hapus `spotify_url` dari array validated (soalnya kolom di DB namanya `spotify_track_id`).
- `Message::create($validated)` — simpan ke DB.
- Redirect ke `/messages?kelas=...` biar user langsung liat pesannya di kelas yang sesuai + flash message `success`.

#### Method `extractSpotifyTrackId(string $url)`
- Pake regex `preg_match('/spotify\.com\/track\/([a-zA-Z0-9]+)/', ...)`.
- `$matches[1]` itu track ID-nya. Kalo gak cocok, return `null`.
- Contoh: `https://open.spotify.com/track/4PTG3Z6ehG...` → `4PTG3Z6ehG...`

---

### 3.3 `app/Models/Message.php` — Model Eloquent

```php
class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipient_name',
        'kelas',
        'message',
        'spotify_track_id',
    ];
}
```

**Sederhana banget.** Gak ada casts, gak ada relationships, gak ada accessors/mutators. `$fillable` ngontrol kolom mana yang boleh diisi massal lewat `Message::create()`.

---

### 3.4 `database/migrations/2025_01_01_000001_create_messages_table.php` — Struktur Tabel

```php
Schema::create('messages', function (Blueprint $table) {
    $table->id();                          // auto increment PK
    $table->string('recipient_name');      // nama penerima
    $table->string('kelas');               // kelas (ex: "XI PPLG 1")
    $table->text('message');               // isi pesan
    $table->string('spotify_track_id')->nullable(); // ID lagu Spotify (boleh kosong)
    $table->timestamps();                  // created_at + updated_at
});
```

**5 kolom data + 2 timestamp.** Gak ada IP, gak ada user agent, gak ada session — sesuai PRD soal anonimitas.

---

### 3.5 `resources/views/welcome.blade.php` — Halaman Utama

Halaman ini adalah single-page yang berisi:

| Bagian | Deskripsi |
|--------|-----------|
| **Header** | Logo "SendTheSong" + nav: browse & tell your story |
| **Stats Cards** | 3 card: total stories, classes reached, latest story |
| **Marquee 1** | Nama penerima & kelas jalan dari kanan ke kiri (marquee) |
| **Marquee 2** | Isi pesan (dipotong 60 karakter) jalan dari kiri ke kanan (marquee-reverse) |
| **Form** | Input: recipient_name, kelas (select dropdown 24 opsi), message (textarea), spotify_url |
| **Footer** | Copyright SMK Negeri 1 |

**Yang perlu lo notice:**
- Marquee pake duplikasi konten (2 div) biar infinite loop mulus. CSS animation `translateX(-50%)`.
- `@csrf` di form — wajib Laravel buat proteksi CSRF.
- Form POST ke `/messages`, setelah sukses redirect ke `/messages?kelas=...`.

---

### 3.6 `resources/views/messages/index.blade.php` — Halaman Browse

| Bagian | Deskripsi |
|--------|-----------|
| **Header** | Sama kayak welcome, tapi nav-nya terbalik (browse aktif, tell your story jadi link) |
| **Search Bar** | Input text + select filter kelas + button search & reset |
| **Grid Cards** | 3 kolom (responsive: 1 → 2 → 3), looping $messages |
| **Card** | Nama (font-reenie besar), kelas + waktu, isi pesan, Spotify iframe (height 80px) |
| **Pagination** | `$messages->appends(request()->query())->links()` — biar filter gak ilang pas pindah halaman |
| **Empty State** | Kalo gak ada pesan, tampilin "no stories found" |

**Spotify Embed:**
```html
<iframe src="https://open.spotify.com/embed/track/{{ $msg->spotify_track_id }}?utm_source=generator"
        width="100%" height="80" frameborder="0"
        allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"
        loading="lazy">
</iframe>
```
- Cuma tampil kalo `$msg->spotify_track_id` gak null.
- Height 80px = compact player.
- `loading="lazy"` biar gak nge-load semua iframe sekaligus.

---

### 3.7 `resources/css/app.css` — Tailwind + Custom Animations

```css
@import 'tailwindcss';                     // Tailwind v4 (pake @import, bukan @tailwind)
@source ...                                // directive buat scanning source file Tailwind
@theme {
    --font-sans: 'Plus Jakarta Sans', ...;
    --font-reenie: 'Reenie Beanie', cursive;
    --animate-marquee: marquee 30s linear infinite;
    --animate-marquee-reverse: marquee-reverse 30s linear infinite;
}

@keyframes marquee { 0% { translateX(0); } 100% { translateX(-50%); } }
@keyframes marquee-reverse { 0% { translateX(-50%); } 100% { translateX(0); } }
```

**Yang perlu lo notice:**
- Tailwind v4 pake `@import` bukan `@tailwind` (berbeda dari v3).
- Dua animasi marquee: satu ke kiri, satu ke kanan.
- Marquee jalan 30 detik sekali putaran, infinite.

---

## 4. Regex Spotify Extraction

```php
private function extractSpotifyTrackId(string $url): ?string
{
    preg_match('/spotify\.com\/track\/([a-zA-Z0-9]+)/', $url, $matches);
    return $matches[1] ?? null;
}
```

**Cara kerja:**
1. Regex nyari pattern `spotify.com/track/` diikuti karakter alfanumerik.
2. `([a-zA-Z0-9]+)` = capture group yang nangkep track ID-nya.
3. `$matches[1]` berisi ID (misal `4PTG3Z6ehG...`).
4. Kalo gak cocok (URL invalid), return `null`.
5. Di `store()`, hasilnya disimpen ke kolom `spotify_track_id`.

**Contoh:**
| URL | Hasil Ekstraksi |
|-----|----------------|
| `https://open.spotify.com/track/4PTG3Z6ehG3jhSMvqY4QFY` | `4PTG3Z6ehG3jhSMvqY4QFY` |
| `https://open.spotify.com/track/abc123DEF` | `abc123DEF` |
| `https://youtu.be/dQw4w9WgXcQ` | `null` |

**Kenapa gak simpan URL asli?** Biar bisa dipake langsung di iframe embed Spotify. Format embed: `https://open.spotify.com/embed/track/{track_id}`.

---

## 5. Changelog / Catatan Perubahan

> Bagian ini mencatat setiap perubahan yang gw (opencode) lakukan ke project ini.

| Tanggal | Perubahan | File yang Diubah | Keterangan |
|---------|-----------|------------------|------------|
| 2026-07-07 | Buat INTERNALS.md | `INTERNALS.md` | Dokumentasi jeroan project pertama |
| 2026-07-07 | Comment inline semua file kode | `routes/web.php`, `MessageController.php`, `Message.php`, `2025_01_01_000001_create_messages_table.php`, `welcome.blade.php`, `messages/index.blade.php`, `app.css` | Tiap baris dikasih penjelasan Bahasa Indonesia |

---

## 6. Tips Buat Lo

### 6.1 Cara Baca & Paham Kode Ini

1. **Mulai dari `routes/web.php`** — ini pintu masuk. Lo liat URL apa aja yang ada.
2. **Trace satu rute**: misal `GET /messages` → panggil `MessageController@index` → baca method `index()` → liat view `messages/index.blade.php`.
3. **Pahami Model**: `Message.php` — kolom apa aja yang bisa diisi.
4. **Liat Migration**: `2025_01_01_000001_create_messages_table.php` — liat struktur tabel di DB.
5. **Balik ke View**: pelajari Blade syntax-nya (`@if`, `@foreach`, `{{ }}`, `@csrf`, dll).

### 6.2 Tools Buat Eksplorasi

- `php artisan route:list` — liat semua rute yang terdaftar.
- `php artisan tinker` — interactive shell buat test query.
- `php artisan make:controller NamaController` — bikin controller baru.
- `php artisan make:model NamaModel -m` — bikin model + migration sekaligus.

### 6.3 Konsep Laravel yang Lo Need Tahu

| Konsep | Ada di Project? | Lokasi |
|--------|----------------|--------|
| Route | ✅ | `routes/web.php` |
| Controller | ✅ | `MessageController.php` |
| Model + Eloquent | ✅ | `Message.php` |
| Migration | ✅ | `2025_01_01_000001_create_messages_table.php` |
| Blade Template | ✅ | `welcome.blade.php`, `index.blade.php` |
| Form Request Validation | ✅ | `$request->validate()` di `store()` |
| Pagination | ✅ | `paginate(10)` + `->links()` |
| Session Flash Message | ✅ | `with('success', ...)` + `session('success')` |
| CSRF Protection | ✅ | `@csrf` di form |
| Eloquent Query Builder | ✅ | `where()`, `latest()`, `paginate()` |
| Tailwind CSS | ✅ | Di blade + `app.css` |
| Vite | ✅ | `@vite()` directive |
| Middleware | ❌ | Gak dipake |
| Auth / Login | ❌ | Anonim — gak perlu |
| Relationships | ❌ | Cuma 1 tabel |
| API Routes | ❌ | Cuma web routes |
| Queue / Job | ❌ | Gak dipake |
| Event / Listener | ❌ | Gak dipake |

### 6.4 Alur Develop

1. PRD udah jelas → baca fitur yang mau dikerjain.
2. Kalo butuh tabel baru → bikin migration (`php artisan make:migration`).
3. Kalo butuh model baru → bikin model.
4. Kalo butuh logic baru → bikin method di controller atau bikin controller baru.
5. Kalo butuh halaman baru → bikin blade view.
6. Trace: Route → Controller → Model → View. Kalo salah satu ilang, error.

### 6.5 Command Penting

```bash
# Jalanin server
php artisan serve

# Bikin migration
php artisan make:migration create_nama_tabel

# Jalanin migration
php artisan migrate

# Bikin controller
php artisan make:controller NamaController

# Bikin model + migration
php artisan make:model Nama -m

# Reset DB (hapus semua data)
php artisan migrate:fresh

# Liat semua rute
php artisan route:list

# Compile asset (Tailwind)
npm run build
# atau
npm run dev
```

---

> **Last updated:** 2026-07-07
>
> File ini bakal diupdate setiap ada perubahan kodingan. Kalo lo baca dan ada yang kurang jelas, tanya aja — gw jelasin lebih dalem.
