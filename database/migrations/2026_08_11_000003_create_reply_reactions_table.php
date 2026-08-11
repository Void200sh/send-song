<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ─── TABEL REAKSI BALASAN (ala WhatsApp, per balasan) ───
     * Satu baris = satu reaksi emoji dari satu pengunjung (dilacak via IP).
     * Unik (reply_id, emoji, ip_address) → orang yang sama cuma bisa
     * memberi SATU reaksi per emoji per balasan; klik lagi = batal (toggle).
     */
    public function up(): void
    {
        Schema::create('reply_reactions', function (Blueprint $table) {
            $table->id();
            // Balasan yang di-reaksi — kalau balasan dihapus, reaksinya ikut terhapus
            $table->foreignId('reply_id')->constrained('message_replies')->cascadeOnDelete();
            $table->string('emoji', 16);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->unique(['reply_id', 'emoji', 'ip_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reply_reactions');
    }
};
