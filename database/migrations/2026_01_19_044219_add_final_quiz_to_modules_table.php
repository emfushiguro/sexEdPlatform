<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->foreignId('final_quiz_id')->nullable()->after('is_premium')->constrained('quizzes')->onDelete('set null');
            $table->integer('certificate_pass_score')->default(80)->after('final_quiz_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropForeign(['final_quiz_id']);
            $table->dropColumn(['final_quiz_id', 'certificate_pass_score']);
        });
    }
};
