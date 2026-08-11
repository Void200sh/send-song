<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ─── TABEL AUDIT LOG AKTIVITAS ADMIN ───
     * Jejak semua aksi admin: login/logout, hapus pesan, ban IP, resolve laporan,
     * pin pesan, hapus balasan, export data, dll.
     * Kalau ada yang nyeleneh di panel admin, siapa melakukannya bisa terlacak.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            // Admin yang melakukan aksi (null = aksi sistem / admin sudah dihapus)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // Nama admin (snapshot — tetap tersimpan walau user dihapus)
            $table->string('user_name')->nullable();
            // Aksi yang dilakukan, ex: 'messages.destroy', 'reports.ban-ip', 'auth.login'
            $table->string('action', 100);
            // Jenis & id target, ex: target_type='message', target_id=12
            $table->string('target_type', 50)->nullable();
            $table->string('target_id')->nullable();
            // Detail tambahan (json) — misal nama pesan, alasan ban, IP yang diban
            $table->json('details')->nullable();
            // IP & user agent admin saat melakukan aksi
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 300)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
