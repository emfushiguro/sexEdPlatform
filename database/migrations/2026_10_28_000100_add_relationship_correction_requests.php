<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_child_accounts', function (Blueprint $table): void {
            $table->json('relationship_correction_request')->nullable();
        });

        Schema::table('guardian_relationship_verification_documents', function (Blueprint $table): void {
            $table->string('review_status', 32)->nullable();
        });
    }

    public function down(): void
    {
        // Preserve correction instructions and historical review decisions on rollback.
    }
};
