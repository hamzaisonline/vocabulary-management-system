<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practice_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_session_id')->constrained('practice_sessions')->cascadeOnDelete();
            $table->foreignId('vocabulary_word_id')->constrained('vocabulary_words')->cascadeOnDelete();
            $table->text('submitted_answer')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->timestamp('attempted_at')->useCurrent();
            $table->timestamps();

            $table->unique(['practice_session_id', 'vocabulary_word_id'], 'practice_session_word_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practice_attempts');
    }
};
