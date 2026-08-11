<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ─── METHOD UP — BUAT TABEL STIKER ───
     * Stiker diunggah admin (hanya lewat dashboard admin) lalu dipakai
     * pengunjung sebagai pelengkap balasan (thread mini di halaman pesan).
     */
    public function up(): void
    {
        Schema::create('stickers', function (Blueprint $table) {
            $table->id();
            // Label opsional buat admin (misal "love", "teriak") — tidak wajib diisi
            $table->string('name')->nullable();
            // Path relatif file stiker di disk 'public' (storage/app/public/stickers/...)
            $table->string('path');
            // Stiker nonaktif disembunyikan dari picker balasan publik
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stickers');
    }
};
