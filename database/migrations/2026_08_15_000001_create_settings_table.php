<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ─── TABEL SETTINGS (key/value) ───
     * Simpanan pengaturan aplikasi yang bisa diubah dari panel admin,
     * misal "photos_enabled" (fitur foto kamera aktif/nonaktif).
     * value bertipe string (disimpan mentah); casting ditangani pemanggil.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
