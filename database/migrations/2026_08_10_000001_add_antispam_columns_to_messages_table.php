<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('sender_name')->index();
            $table->string('sender_key')->nullable()->after('ip_address')->index();
            $table->string('spam_identity_key', 64)->nullable()->after('sender_key')->index();
            $table->string('spam_fingerprint', 64)->nullable()->after('spam_identity_key')->index();
            $table->boolean('is_spam')->default(false)->after('spam_fingerprint')->index();
            $table->text('spam_reason')->nullable()->after('is_spam');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn([
                'ip_address',
                'sender_key',
                'spam_identity_key',
                'spam_fingerprint',
                'is_spam',
                'spam_reason',
            ]);
        });
    }
};
