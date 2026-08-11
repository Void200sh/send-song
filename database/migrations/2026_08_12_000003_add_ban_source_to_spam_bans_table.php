<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ─── TAMBAH KOLOM SUMBER BAN DI SPAM_BANS ───
     * 'ban_source' : 'auto' (deteksi spam otomatis) | 'manual' (diblokir admin)
     * 'banned_by'  : user_id admin yang memblokir (null untuk ban otomatis)
     * Biar halaman manajemen IP ter-ban bisa membedakan & menampilkan siapa pemban.
     */
    public function up(): void
    {
        Schema::table('spam_bans', function (Blueprint $table) {
            $table->string('ban_source', 10)->default('auto');
            $table->foreignId('banned_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('spam_bans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('banned_by');
            $table->dropColumn('ban_source');
        });
    }
};
