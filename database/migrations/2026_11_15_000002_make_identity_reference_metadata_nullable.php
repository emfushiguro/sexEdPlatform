<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('identity_reference_examples', function (Blueprint $table): void {
            $table->string('issuer', 120)->nullable()->change();
            $table->string('version', 120)->nullable()->change();
            $table->date('published_on')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Preserve references with blank metadata when rolling back.
    }
};
