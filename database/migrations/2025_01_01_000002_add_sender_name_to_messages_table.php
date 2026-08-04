<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ─── METHOD UP — TAMBAHKAN KOLOM sender_name ───
     * Kolom baru buat nyimpen nama pengirim (opsional).
     * Nullable = kalo pengirim milih anonim, kolom ini diisi NULL.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('sender_name')->nullable()->after('recipient_name');
        });
    }

    /**
     * ─── METHOD DOWN — BATALKAN MIGRASI ───
     * Hapus kolom sender_name kalo di-rollback.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('sender_name');
        });
    }
};
