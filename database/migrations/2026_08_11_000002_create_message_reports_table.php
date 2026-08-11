<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ─── METHOD UP — JALANKAN MIGRASI ───
     * Tabel 'message_reports' — laporan pengunjung untuk satu pesan (konten tidak pantas).
     * Admin melihat laporan ini di halaman admin, lalu bisa ban IP / hapus pesan.
     */
    public function up(): void
    {
        Schema::create('message_reports', function (Blueprint $table) {
            $table->id();
            // Pesan yang dilaporkan — cascade: hapus pesan → laporannya ikut hilang
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            // IP pelapor (siapa yang menekan tombol lapor)
            $table->string('ip_address', 45)->nullable();
            // Alasan singkat (opsional) dari pelapor
            $table->string('reason')->nullable();
            // Sudah ditindaklanjuti admin atau belum
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();

            $table->index('message_id');
            $table->index('is_resolved');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_reports');
    }
};
