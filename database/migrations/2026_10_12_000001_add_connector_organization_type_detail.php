<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('connectors', function (Blueprint $table): void {
            $table->string('organization_type_detail')->nullable()->after('organization_type');
        });
    }

    public function down(): void
    {
        Schema::table('connectors', function (Blueprint $table): void {
            $table->dropColumn('organization_type_detail');
        });
    }
};
