<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ─── TABEL SARAN & KRITIK PENGUNJUNG ───
     * Form saran & kritik muncul sebagai modal setelah pengunjung berhasil
     * mengirim story. Isinya (saran & kritik) tersimpan di sini dan dilihat
     * admin di halaman "Saran & Kritik".
     */
    public function up(): void
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            // Kolom saran & kritik — keduanya opsional, minimal satu yang terisi
            $table->text('saran')->nullable();
            $table->text('kritik')->nullable();
            // IP pengunjung pengirim (untuk moderasi)
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};
