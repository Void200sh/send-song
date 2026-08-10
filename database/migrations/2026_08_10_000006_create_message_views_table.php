<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ─── TABEL VIEWS PER PENGUNJUNG ───
     * Satu baris = satu pengunjung (dilacak via IP) yang pernah melihat satu pesan.
     * Unik (message_id, ip_address) → IP yang sama cuma dihitung SEKALI sebagai
     * pengunjung unik. Total kunjungan tetap bertambah lewat kolom messages.views.
     */
    public function up(): void
    {
        Schema::create('message_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->unique(['message_id', 'ip_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_views');
    }
};
