<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quran_verses', function (Blueprint $table) {
            $table->string('qiraah')->default('asim')->after('ayah_text_simple');
            $table->string('riwayah')->default('hafs')->after('qiraah');
            $table->index(['qiraah', 'riwayah']);
        });
    }

    public function down(): void
    {
        Schema::table('quran_verses', function (Blueprint $table) {
            $table->dropIndex(['qiraah', 'riwayah']);
            $table->dropColumn(['qiraah', 'riwayah']);
        });
    }
};

