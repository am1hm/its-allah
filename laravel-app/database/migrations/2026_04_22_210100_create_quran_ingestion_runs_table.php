<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_ingestion_runs', function (Blueprint $table) {
            $table->id();
            $table->string('source_id');
            $table->string('run_type');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('status')->default('running');
            $table->unsignedInteger('rows_processed')->default(0);
            $table->unsignedInteger('rows_inserted')->default(0);
            $table->unsignedInteger('rows_updated')->default(0);
            $table->unsignedInteger('rows_skipped')->default(0);
            $table->unsignedInteger('mismatch_count')->default(0);
            $table->string('output_report_path')->nullable();
            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_ingestion_runs');
    }
};

