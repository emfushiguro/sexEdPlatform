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
        Schema::table('instructor_profiles', function (Blueprint $table) {
            $table->string('educational_background')->nullable()->after('bio');
            $table->text('professional_background')->nullable()->after('educational_background');
            $table->string('primary_expertise')->nullable()->after('specialization');
            $table->json('expertise_tags')->nullable()->after('primary_expertise');
            $table->unsignedInteger('years_experience')->nullable()->after('expertise_tags');
            $table->json('certifications')->nullable()->after('years_experience');
            $table->string('profile_photo_path')->nullable()->after('certifications');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instructor_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'educational_background',
                'professional_background',
                'primary_expertise',
                'expertise_tags',
                'years_experience',
                'certifications',
                'profile_photo_path',
            ]);
        });
    }
};