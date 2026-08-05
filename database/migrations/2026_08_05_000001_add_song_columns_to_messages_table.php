<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('song_title')->nullable()->after('spotify_track_id');
            $table->string('song_artist')->nullable()->after('song_title');
            $table->string('cover_url')->nullable()->after('song_artist');
            $table->string('youtube_video_id')->nullable()->after('cover_url');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['song_title', 'song_artist', 'cover_url', 'youtube_video_id']);
        });
    }
};
