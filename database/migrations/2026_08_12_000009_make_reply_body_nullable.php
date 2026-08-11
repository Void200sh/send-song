<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ─── METHOD UP — BIKIN KOLOM BODY OPSIONAL ───
     * Supaya pengunjung bisa kirim balasan stiker SAJA tanpa teks
     * (body diisi null, stiker di kolom sticker_path).
     */
    public function up(): void
    {
        Schema::table('message_replies', function (Blueprint $table) {
            $table->text('body')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('message_replies', function (Blueprint $table) {
            $table->text('body')->nullable(false)->change();
        });
    }
};
