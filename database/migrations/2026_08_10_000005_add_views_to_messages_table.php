<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ─── KOLOM VIEWS ───
     * views         — total berapa kali pesan ini dilihat (bertambah tiap kartu
     *                 tampil di feed ATAU halaman detail dibuka).
     * unique_views  — berapa pengunjung UNIK (per IP) yang pernah melihat pesan ini.
     *                 Angkanya dijamin lewat tabel message_views (pola sama seperti
     *                 message_reactions biar IP yang sama gak dihitung 2×).
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('unique_views')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['views', 'unique_views']);
        });
    }
};
