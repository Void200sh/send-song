<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ─── METHOD UP — TAMBAH KOLOM PARENT_ID (REPLY KOMENTAR) ───
     * Balasan bisa membalas balasan lain (1 tingkat): komentar root
     * (parent_id null) + anaknya (parent_id mengarah ke root).
     * Kalau parent dihapus, anak-anaknya ikut terhapus (cascade).
     */
    public function up(): void
    {
        Schema::table('message_replies', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('message_id')
                ->constrained('message_replies')
                ->cascadeOnDelete();

            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::table('message_replies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
