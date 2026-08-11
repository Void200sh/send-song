<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ─── TABEL LOG LOGIN ADMIN ───
     * Riwayat percobaan login (sukses & gagal) ke panel admin.
     * 'is_suspicious' = login sukses dari IP yang belum pernah dipakai user ini
     * sebelumnya — ciri akun diakses dari perangkat/alamat baru.
     */
    public function up(): void
    {
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('status', 10); // 'success' | 'failed'
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 300)->nullable();
            $table->boolean('is_suspicious')->default(false);
            $table->boolean('is_new')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index('is_suspicious');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_logs');
    }
};
