<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->text('raw_text')->nullable();
            $table->string('crop_path')->nullable();
            $table->decimal('score', 8, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->boolean('needs_review')->default(false);
            $table->timestamp('graded_at')->nullable();
            $table->json('grade_metadata')->nullable();
            $table->string('status');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['submission_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_answers');
    }
};
