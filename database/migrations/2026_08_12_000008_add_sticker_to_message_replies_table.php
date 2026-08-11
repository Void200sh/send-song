<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ─── METHOD UP — TAMBAH KOLOM STIKER DI BALASAN ───
     * Kolom `sticker_path` menyimpan path relatif stiker yang dipilih
     * pengunjung lewat picker stiker (disk 'public').
     * Nullable = stiker opsional, balasan teks biasa tetap normal.
     */
    public function up(): void
    {
        Schema::table('message_replies', function (Blueprint $table) {
            $table->string('sticker_path')->nullable()->after('photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('message_replies', function (Blueprint $table) {
            $table->dropColumn('sticker_path');
        });
    }
};
