<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ─── METHOD UP — TAMBAH KOLOM sender_ip ───
     * Nyimpen alamat IP pengirim pesan (buat pantauan admin).
     * Panjang 45 karakter biar muat IPv4, IPv6, maupun IPv4-mapped IPv6.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('sender_ip', 45)->nullable()->after('kelas');
        });
    }

    /**
     * ─── METHOD DOWN — HAPUS KOLOM sender_ip ───
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('sender_ip');
        });
    }
};
