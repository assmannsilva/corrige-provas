<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocr_processings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete();
            $table->string('status')->default('processing');
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocr_processings');
    }
};
