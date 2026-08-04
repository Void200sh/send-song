<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ─── METHOD UP — JALANKAN MIGRASI ───
     * PHP artisan migrate bakal ngejalanin ini
     * Isinya: bikin tabel 'messages' di database
     */
    public function up(): void
    {
        // Schema::create — bikin tabel baru namanya 'messages'
        Schema::create('messages', function (Blueprint $table) {
            // id — auto increment, big integer, primary key
            $table->id();
            // recipient_name — string (VARCHAR), wajib diisi (not null)
            // Nyimpen nama orang yang dituju
            $table->string('recipient_name');
            // kelas — string, wajib diisi
            // Nyimpen kelas penerima (ex: "XI PPLG 1")
            $table->string('kelas');
            // message — TEXT (bisa panjang), wajib diisi
            // Nyimpen isi pesan rahasianya
            $table->text('message');
            // spotify_track_id — string, BOLEH KOSONG (nullable)
            // Nyimpen ID track Spotify hasil ekstraksi
            // Nullable = kalo user gak ngirim link lagu, kolom ini diisi NULL
            $table->string('spotify_track_id')->nullable();
            // timestamps — otomatis bikin kolom created_at sama updated_at
            // Diisi otomatis sama Laravel pas record dibuat/diupdate
            $table->timestamps();
        });
    }

    /**
     * ─── METHOD DOWN — BATALKAN MIGRASI ───
     * PHP artisan migrate:rollback bakal ngejalanin ini
     * Isinya: hapus tabel 'messages' (kalo ada)
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
