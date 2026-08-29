<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_vocabulary_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('vocabulary_level_id')->constrained('vocabulary_levels')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['class_id', 'vocabulary_level_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_vocabulary_levels');
    }
};
