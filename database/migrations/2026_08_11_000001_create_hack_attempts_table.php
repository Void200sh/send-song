<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hack_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->index();
            $table->string('method', 10);
            $table->string('path', 500)->index();
            $table->text('query_string')->nullable();
            $table->text('payload')->nullable();
            $table->string('user_agent', 300)->nullable();
            $table->string('reason', 255);
            $table->string('signature', 100)->index();
            $table->string('severity', 20)->default('medium')->index();
            $table->boolean('is_new')->default(true)->index();
            $table->unsignedInteger('count')->default(1);
            $table->timestamps();

            // Buat pencarian dedup (IP + pola dalam 10 menit) tetap cepat
            $table->index(['ip_address', 'signature', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hack_attempts');
    }
};
