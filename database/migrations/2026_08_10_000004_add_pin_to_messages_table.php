<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ─── FITUR PIN OLEH ADMIN ───
     * is_pinned: boolean — pesan di-pin tampil paling atas di feed publik.
     * pinned_at: timestamp — waktu di-pin (buat urutan antar pesan ter-pin).
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->boolean('is_pinned')->default(false)->index();
            $table->timestamp('pinned_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['is_pinned', 'pinned_at']);
        });
    }
};
