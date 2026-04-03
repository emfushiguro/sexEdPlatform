<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->string('content_owner_type')->default('instructor')->after('created_by');
            $table->foreignId('published_revision_id')->nullable()->after('final_quiz_id');
            $table->foreignId('published_by_admin_id')->nullable()->after('published_revision_id');
            $table->string('current_review_status')->nullable()->after('published_by_admin_id');

            $table->foreign('published_revision_id')
                ->references('id')
                ->on('module_revisions')
                ->nullOnDelete();

            $table->foreign('published_by_admin_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(['content_owner_type', 'current_review_status']);
        });
    }

    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropForeign(['published_revision_id']);
            $table->dropForeign(['published_by_admin_id']);
            $table->dropIndex(['content_owner_type', 'current_review_status']);
            $table->dropColumn([
                'content_owner_type',
                'published_revision_id',
                'published_by_admin_id',
                'current_review_status',
            ]);
        });
    }
};
