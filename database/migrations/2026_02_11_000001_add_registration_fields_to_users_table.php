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
        Schema::table('users', function (Blueprint $table) {
            $table->string('middle_initial', 10)->nullable()->after('first_name');
            $table->string('suffix', 10)->nullable()->after('last_name');
            $table->date('birthdate')->nullable()->after('email');
            $table->integer('age')->nullable()->after('birthdate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['middle_initial', 'suffix', 'birthdate', 'age']);
        });
    }
};
