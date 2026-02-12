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
        // Update learner_profiles table
        Schema::table('learner_profiles', function (Blueprint $table) {
            $table->foreignId('parent_user_id')->nullable()->after('user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_parent_account')->default(false)->after('parent_user_id');
            $table->boolean('requires_parental_consent')->default(false)->after('is_parent_account');
        });

        // Create parent_child_accounts table for relationship tracking
        Schema::create('parent_child_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('child_user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('can_view_progress')->default(true);
            $table->boolean('can_view_quiz_answers')->default(true);
            $table->boolean('can_approve_content')->default(false);
            $table->timestamp('relationship_verified_at')->nullable();
            $table->timestamps();
            
            // Ensure a child can only have one parent and no duplicates
            $table->unique(['parent_user_id', 'child_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_child_accounts');
        
        Schema::table('learner_profiles', function (Blueprint $table) {
            $table->dropForeign(['parent_user_id']);
            $table->dropColumn(['parent_user_id', 'is_parent_account', 'requires_parental_consent']);
        });
    }
};
