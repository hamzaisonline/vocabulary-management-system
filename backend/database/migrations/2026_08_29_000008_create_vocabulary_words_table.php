<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vocabulary_words', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vocabulary_level_id')
                ->constrained('vocabulary_levels')
                ->cascadeOnDelete();
            $table->string('word');
            $table->string('translation');
            $table->text('example')->nullable();
            $table->text('notes')->nullable();
            $table->string('audio_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vocabulary_words');
    }
};
