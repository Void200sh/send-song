<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ─── TABEL REAKSI EMOJI (ala WhatsApp) ───
     * Satu baris = satu reaksi emoji dari satu pengunjung (dilacak via IP).
     * Unik (message_id, emoji, ip_address) → orang yang sama cuma bisa
     * memberi SATU reaksi per emoji; klik lagi = batal (toggle).
     */
    public function up(): void
    {
        Schema::create('message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->string('emoji', 16);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->unique(['message_id', 'emoji', 'ip_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_reactions');
    }
};
