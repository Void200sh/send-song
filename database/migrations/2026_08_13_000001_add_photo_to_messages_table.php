<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ─── KOLOM FOTO KAMERA ───
     * photo_path — path relatif foto hasil jepretan kamera (disimpan di disk
     *              "public" → storage/app/public/photos/YYYY/xxx.jpg).
     *              Null = pesan tanpa foto (opsional).
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('theme');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
    }
};
