# Product Requirement Document (PRD) – Send Song SMK

## 1. Ringkasan Projek

* **Nama Produk:** Send Song SMK
* **Deskripsi:** Platform web anonim tempat siswa dapat mengirimkan pesan rahasia (menfess) yang ditujukan untuk seseorang di lingkungan sekolah berdasarkan nama dan kelas, dilengkapi dengan lagu yang didedikasikan via Spotify Embed Player.
* **Tujuan:** Menyediakan wadah ekspresi digital dan hiburan antar siswa di sekolah.

---

## 2. Pengguna Utama (User Persona)

1. **Pengirim (Anonim):** Siswa yang ingin mengirimkan pesan dan lagu tanpa perlu registrasi atau membagikan identitas asli.
2. **Penerima / Pengunjung:** Siswa yang mengakses website untuk mencari pesan yang ditujukan kepada nama mereka atau memantau pesan masuk di kelas tertentu.

---

## 3. Fitur Utama & Kebutuhan Fungsional

### 3.1 Form Pengiriman Pesan (Halaman Utama)

* **Input Nama Penerima:** Teks bebas (wajib diisi).
* **Dropdown Opsi Kelas:** Pilihan tingkat (X, XI, XII) dan rumpun jurusan sekolah (wajib diisi).
* **Textarea Pesan:** Input teks untuk menulis pesan/curhatan (wajib diisi).
* **Input Link Spotify:** Kolom untuk menempelkan (*paste*) URL tautan lagu dari Spotify.

### 3.2 Halaman Jelajah / Feed Publik

* **Timeline Pesan:** Menampilkan seluruh kartu pesan masuk secara kronologis (terbaru di atas).
* **Komponen Kartu Pesan:** Menampilkan informasi Nama Penerima, Kelas, Isi Pesan, Waktu Kirim, dan Widget Spotify Embed Player yang dapat memutar musik secara langsung.
* **Pagination:** Pembatasan jumlah tampilan pesan per halaman untuk menjaga performa loading web.

### 3.3 Fitur Pencarian & Filter

* **Pencarian Nama:** Kolom pencarian untuk menyaring pesan berdasarkan nama penerima tertentu.
* **Filter Kelas:** Dropdown filter untuk menampilkan pesan khusus yang ditujukan pada kelas tertentu.

---

## 4. Arsitektur Data & Struktur Tabel Database

### Tabel: `messages`

| Nama Kolom | Tipe Data | Atribut | Deskripsi |
| --- | --- | --- | --- |
| `id` | BigInteger | Primary Key, Auto Increment | ID unik setiap pesan. |
| `recipient_name` | String | Not Null | Nama target penerima pesan. |
| `kelas` | String | Not Null | Kelas target penerima (ex: "XI PPLG 1"). |
| `message` | Text | Not Null | Isi dari pesan rahasia. |
| `spotify_track_id` | String | Nullable | ID unik track Spotify hasil ekstraksi dari URL. |
| `created_at` | Timestamp | Nullable | Waktu pesan dibuat otomatis oleh framework. |
| `updated_at` | Timestamp | Nullable | Waktu pesan diperbarui otomatis oleh framework. |

---

## 5. Kebutuhan Nonfungsional & Alur Logika Sistem

* **Framework:** Laravel 11+ / 12+ & PHP 8.2+.
* **Frontend:** Tailwind CSS via Blade Template.
* **Ekstraksi URL Spotify:** Sistem backend harus memiliki fungsi pendeteksi *regex* untuk memotong URL Spotify umum (contoh: `https://open.spotify.com/track/4PTG3Z6ehG...`) menjadi format ringkas `4PTG3Z6ehG...` sebelum disimpan ke kolom `spotify_track_id`, guna diintegrasikan ke dalam URL Iframe Embed (`https://open.spotify.com/embed/track/{spotify_track_id}`).
* **Keamanan Mutlak:** Tidak menyimpan alamat IP, session user, atau data pengenal apapun milik pengirim ke dalam database demi menjaga privasi anonimitas penuh.

Perbedaan & Detail Desain yang Perlu Ditambahkan:
Vibe & Tema Warna (Dark Mode-Centric):

Send The Song aslinya menggunakan tema Dark Mode yang sangat kental (background hitam pekat atau abu-abu gelap #0b0b0b).

Desain kartu (card) menggunakan efek semi-transparan (Glassmorphism) dengan border tipis berwarna abu-abu redup agar terlihat modern.

Gaya Penulisan Teks pada Kartu Pesan:

Judul kartu biasanya menggunakan format: "To: [Nama Penerima]" dengan ukuran font yang cukup besar dan tebal.

Di bawahnya terdapat teks kecil pembantu berupa kelas dan keterangan waktu (contoh: "XI PPLG 1 • 2 hours ago").

Tampilan Form Input yang Minimalis:

Kotak inputan form tidak menggunakan warna putih solid, melainkan transparan dengan border abu-abu gelap, dan berubah menjadi putih/biru menyala saat diklik (focus state).

Karakteristik Widget Spotify:

Ukuran Embed Player Spotify diatur menggunakan tipe Compact (tinggi sekitar 80px atau 152px), bukan yang kotak besar, agar pas diletakkan di dalam kartu pesan tanpa memakan terlalu banyak tempat.``