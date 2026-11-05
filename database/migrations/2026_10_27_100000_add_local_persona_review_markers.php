<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learner_identity_verifications', function (Blueprint $table): void {
            $table->boolean('sandbox_manual_review')->default(false);
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('parent_identity_sandbox_manual_review')->default(false);
        });
    }

    public function down(): never
    {
        throw new LogicException('Test-only verification provenance must be retained.');
    }
};
