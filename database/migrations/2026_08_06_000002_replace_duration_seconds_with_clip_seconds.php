<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedInteger('clip_start_seconds')->nullable()->after('youtube_video_id');
            $table->unsignedInteger('clip_end_seconds')->nullable()->after('clip_start_seconds');
        });

        // Data lama: duration_seconds dianggap sebagai akhir klip, mulai dari 0
        DB::table('messages')->whereNotNull('duration_seconds')->update([
            'clip_start_seconds' => 0,
            'clip_end_seconds' => DB::raw('duration_seconds'),
        ]);

        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('duration_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedInteger('duration_seconds')->nullable()->after('youtube_video_id');
        });

        DB::table('messages')->whereNotNull('clip_end_seconds')->update([
            'duration_seconds' => DB::raw('clip_end_seconds'),
        ]);

        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['clip_start_seconds', 'clip_end_seconds']);
        });
    }
};