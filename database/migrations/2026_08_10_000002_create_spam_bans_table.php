<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spam_bans', function (Blueprint $table) {
            $table->id();
            $table->string('sender_key');
            $table->string('sender_name')->nullable();
            $table->string('ip_address', 45);
            $table->unsignedInteger('spam_count')->default(0);
            $table->string('reason')->default('10 spam terdeteksi');
            $table->timestamp('banned_at')->nullable();
            $table->timestamps();

            $table->unique(['sender_key', 'ip_address']);
            $table->index(['ip_address', 'sender_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spam_bans');
    }
};
