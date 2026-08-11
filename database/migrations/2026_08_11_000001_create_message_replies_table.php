<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ─── METHOD UP — JALANKAN MIGRASI ───
     * Tabel 'message_replies' — balasan/komentar untuk satu pesan (thread mini).
     * Satu pesan bisa punya banyak balasan dari banyak pengunjung.
     */
    public function up(): void
    {
        Schema::create('message_replies', function (Blueprint $table) {
            $table->id();
            // Pesan induk — kalau pesan dihapus, semua balasannya ikut terhapus (cascade)
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            // Nama pengirim balasan (opsional — null = anonim)
            $table->string('sender_name')->nullable();
            // Isi balasan
            $table->text('body');
            // IP pengirim balasan (untuk moderasi/kasus laporan)
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            // Query paling umum: ambil semua balasan per pesan
            $table->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_replies');
    }
};
