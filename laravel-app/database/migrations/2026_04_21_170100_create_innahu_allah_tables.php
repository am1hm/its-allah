<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allah_names', function (Blueprint $table) {
            $table->id();
            $table->string('name_arabic');
            $table->string('name_transliteration')->nullable();
            $table->string('meaning')->nullable();
            $table->string('category')->nullable();
            $table->string('source_type')->default('both');
            $table->string('verification_status')->default('pending');
            $table->text('reviewer_notes')->nullable();
            $table->string('source_ref')->nullable();
            $table->string('source_url')->nullable();
            $table->timestamp('ingestion_timestamp')->nullable();
            $table->string('parser_version')->nullable();
            $table->string('source_hash')->nullable();
            $table->timestamps();
        });

        Schema::create('quran_verses', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('surah_number');
            $table->string('surah_name');
            $table->unsignedInteger('ayah_number');
            $table->longText('ayah_text');
            $table->longText('ayah_text_simple')->nullable();
            $table->string('source_ref')->nullable();
            $table->string('source_url')->nullable();
            $table->timestamp('ingestion_timestamp')->nullable();
            $table->string('parser_version')->nullable();
            $table->string('source_hash')->nullable();
            $table->timestamps();
            $table->unique(['surah_number', 'ayah_number']);
        });

        Schema::create('name_verse_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('name_id')->constrained('allah_names')->cascadeOnDelete();
            $table->foreignId('verse_id')->constrained('quran_verses')->cascadeOnDelete();
            $table->text('context_note')->nullable();
            $table->string('verification_status')->default('pending');
            $table->string('source_ref')->nullable();
            $table->string('source_url')->nullable();
            $table->timestamp('ingestion_timestamp')->nullable();
            $table->string('parser_version')->nullable();
            $table->string('source_hash')->nullable();
            $table->timestamps();
            $table->unique(['name_id', 'verse_id']);
        });

        Schema::create('tafsir_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verse_id')->constrained('quran_verses')->cascadeOnDelete();
            $table->string('scholar_name');
            $table->string('source_book')->nullable();
            $table->string('volume')->nullable();
            $table->string('page_number')->nullable();
            $table->longText('tafsir_text');
            $table->string('data_source');
            $table->string('verification_status')->default('pending');
            $table->text('reviewer_notes')->nullable();
            $table->string('source_ref')->nullable();
            $table->string('source_url')->nullable();
            $table->timestamp('ingestion_timestamp')->nullable();
            $table->string('parser_version')->nullable();
            $table->string('source_hash')->nullable();
            $table->timestamps();
        });

        Schema::create('hadiths', function (Blueprint $table) {
            $table->id();
            $table->string('collection');
            $table->string('hadith_number');
            $table->string('book_chapter')->nullable();
            $table->longText('arabic_text');
            $table->longText('isnad')->nullable();
            $table->longText('matn')->nullable();
            $table->string('grade')->nullable();
            $table->string('grading_scholar')->nullable();
            $table->text('takhrij')->nullable();
            $table->string('data_source');
            $table->string('verification_status')->default('pending');
            $table->text('reviewer_notes')->nullable();
            $table->string('source_ref')->nullable();
            $table->string('source_url')->nullable();
            $table->timestamp('ingestion_timestamp')->nullable();
            $table->string('parser_version')->nullable();
            $table->string('source_hash')->nullable();
            $table->timestamps();
        });

        Schema::create('name_hadith_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('name_id')->constrained('allah_names')->cascadeOnDelete();
            $table->foreignId('hadith_id')->constrained('hadiths')->cascadeOnDelete();
            $table->text('relevance_note')->nullable();
            $table->string('verification_status')->default('pending');
            $table->string('source_ref')->nullable();
            $table->string('source_url')->nullable();
            $table->timestamp('ingestion_timestamp')->nullable();
            $table->string('parser_version')->nullable();
            $table->string('source_hash')->nullable();
            $table->timestamps();
            $table->unique(['name_id', 'hadith_id']);
        });

        Schema::create('scholarly_commentary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('name_id')->nullable()->constrained('allah_names')->nullOnDelete();
            $table->foreignId('verse_id')->nullable()->constrained('quran_verses')->nullOnDelete();
            $table->foreignId('hadith_id')->nullable()->constrained('hadiths')->nullOnDelete();
            $table->string('scholar_name');
            $table->string('source_book')->nullable();
            $table->string('volume')->nullable();
            $table->string('page_number')->nullable();
            $table->longText('commentary_text');
            $table->string('commentary_type');
            $table->string('data_source');
            $table->string('verification_status')->default('pending');
            $table->text('reviewer_notes')->nullable();
            $table->string('source_ref')->nullable();
            $table->string('source_url')->nullable();
            $table->timestamp('ingestion_timestamp')->nullable();
            $table->string('parser_version')->nullable();
            $table->string('source_hash')->nullable();
            $table->timestamps();
        });

        Schema::create('father_narrative', function (Blueprint $table) {
            $table->id();
            $table->foreignId('name_id')->nullable()->constrained('allah_names')->nullOnDelete();
            $table->foreignId('verse_id')->nullable()->constrained('quran_verses')->nullOnDelete();
            $table->longText('narrative_text');
            $table->string('status')->default('draft');
            $table->timestamp('created_date')->nullable();
            $table->timestamp('updated_date')->nullable();
            $table->timestamps();
        });

        Schema::create('review_audit_log', function (Blueprint $table) {
            $table->id();
            $table->string('content_type');
            $table->unsignedBigInteger('content_id');
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->string('reviewer')->default('father');
            $table->text('notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_audit_log');
        Schema::dropIfExists('father_narrative');
        Schema::dropIfExists('scholarly_commentary');
        Schema::dropIfExists('name_hadith_links');
        Schema::dropIfExists('hadiths');
        Schema::dropIfExists('tafsir_entries');
        Schema::dropIfExists('name_verse_links');
        Schema::dropIfExists('quran_verses');
        Schema::dropIfExists('allah_names');
    }
};

