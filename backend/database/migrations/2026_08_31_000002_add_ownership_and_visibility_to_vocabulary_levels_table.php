<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vocabulary_levels', function (Blueprint $table) {
            $table->foreignId('created_by_user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->string('visibility', 20)->default('private')->after('created_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('vocabulary_levels', function (Blueprint $table) {
            $table->dropForeign(['created_by_user_id']);
            $table->dropColumn(['created_by_user_id', 'visibility']);
        });
    }
};
