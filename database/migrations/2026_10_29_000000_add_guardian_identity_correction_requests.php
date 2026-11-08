<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->json('parent_identity_requested_corrections')
                ->nullable()
                ->after('parent_identity_resubmission_allowed');
        });
    }

    public function down(): never
    {
        throw new LogicException('Guardian identity correction requests must be retained.');
    }
};
