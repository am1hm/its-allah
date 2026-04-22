<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_verse_diffs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('surah_number');
            $table->unsignedInteger('ayah_number');
            $table->longText('kfgqpc_text')->nullable();
            $table->longText('tanzil_text')->nullable();
            $table->longText('normalized_kfgqpc')->nullable();
            $table->longText('normalized_tanzil')->nullable();
            $table->string('diff_type');
            $table->text('notes')->nullable();
            $table->string('qiraah')->default('asim');
            $table->string('riwayah')->default('hafs');
            $table->timestamps();
            $table->index(['surah_number', 'ayah_number']);
            $table->index(['qiraah', 'riwayah']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_verse_diffs');
    }
};

