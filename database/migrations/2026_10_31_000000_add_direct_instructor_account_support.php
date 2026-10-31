<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('learner', 'organization', 'clinic', 'counselor', 'admin', 'instructor', 'applicant') NOT NULL DEFAULT 'learner'");

        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->string('province_code', 16)->nullable()->after('contact');
            $table->string('city_code', 16)->nullable()->after('province_code');
            $table->string('barangay_code', 16)->nullable()->after('city_code');
            $table->string('address_line')->nullable()->after('barangay_code');

            $table->index('province_code', 'user_profiles_province_code_idx');
            $table->index('city_code', 'user_profiles_city_code_idx');
            $table->index('barangay_code', 'user_profiles_barangay_code_idx');
            $table->foreign('province_code', 'user_profiles_province_code_fk')
                ->references('code')->on('provinces')->nullOnDelete();
            $table->foreign('city_code', 'user_profiles_city_code_fk')
                ->references('code')->on('cities')->nullOnDelete();
            $table->foreign('barangay_code', 'user_profiles_barangay_code_fk')
                ->references('code')->on('barangays')->nullOnDelete();
        });
    }

    public function down(): never
    {
        throw new LogicException(
            'Applicant account and profile history must not be removed by rollback; use a retention-approved follow-up migration.'
        );
    }
};
