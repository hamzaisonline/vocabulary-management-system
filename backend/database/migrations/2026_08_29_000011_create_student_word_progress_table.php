<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_word_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('vocabulary_word_id')->constrained('vocabulary_words')->cascadeOnDelete();
            $table->unsignedInteger('mastery_percent')->default(0);
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedInteger('correct_attempts')->default(0);
            $table->timestamp('last_practiced_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'vocabulary_word_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_word_progress');
    }
};
