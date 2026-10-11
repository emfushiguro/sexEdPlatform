<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table): void {
            $table->uuid('submission_token')->nullable()->unique();
            $table->boolean('consent_given')->default(false)->index();
            $table->boolean('show_role')->default(false);
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('consent_withdrawn_at')->nullable();
        });

        if (Schema::hasColumn('testimonials', 'platform_feedback_id')) {
            if (DB::connection()->getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE testimonials DROP FOREIGN KEY testimonials_platform_feedback_id_foreign');
                DB::statement('ALTER TABLE testimonials DROP INDEX testimonials_platform_feedback_id_unique');
                DB::statement('ALTER TABLE testimonials MODIFY platform_feedback_id BIGINT UNSIGNED NULL');
                DB::statement('ALTER TABLE testimonials ADD UNIQUE KEY testimonials_platform_feedback_id_unique (platform_feedback_id)');
                DB::statement('ALTER TABLE testimonials ADD CONSTRAINT testimonials_platform_feedback_id_foreign FOREIGN KEY (platform_feedback_id) REFERENCES platform_feedback (id) ON DELETE SET NULL');
            } else {
                Schema::table('testimonials', function (Blueprint $table): void {
                    $table->dropForeign(['platform_feedback_id']);
                    $table->dropUnique('testimonials_platform_feedback_id_unique');
                    $table->unsignedBigInteger('platform_feedback_id')->nullable()->change();
                    $table->unique('platform_feedback_id');
                    $table->foreign('platform_feedback_id')->references('id')->on('platform_feedback')->nullOnDelete();
                });
            }
        }

        DB::table('testimonials')
            ->join('platform_feedback', 'platform_feedback.id', '=', 'testimonials.platform_feedback_id')
            ->select([
                'testimonials.id',
                'platform_feedback.testimonial_consent',
                'platform_feedback.testimonial_show_role',
                'platform_feedback.testimonial_consented_at',
                'platform_feedback.testimonial_consent_withdrawn_at',
            ])
            ->orderBy('testimonials.id')
            ->get()
            ->each(function (object $row): void {
                DB::table('testimonials')->where('id', $row->id)->update([
                    'consent_given' => (bool) $row->testimonial_consent,
                    'show_role' => (bool) $row->testimonial_show_role,
                    'consented_at' => $row->testimonial_consented_at,
                    'consent_withdrawn_at' => $row->testimonial_consent_withdrawn_at,
                ]);
            });
    }

    public function down(): void
    {
        if (DB::table('testimonials')->whereNull('platform_feedback_id')->exists()) {
            throw new RuntimeException('Cannot restore the required testimonial ticket link while independent testimonials exist.');
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE testimonials DROP FOREIGN KEY testimonials_platform_feedback_id_foreign');
            DB::statement('ALTER TABLE testimonials DROP INDEX testimonials_platform_feedback_id_unique');
            DB::statement('ALTER TABLE testimonials MODIFY platform_feedback_id BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE testimonials ADD UNIQUE KEY testimonials_platform_feedback_id_unique (platform_feedback_id)');
            DB::statement('ALTER TABLE testimonials ADD CONSTRAINT testimonials_platform_feedback_id_foreign FOREIGN KEY (platform_feedback_id) REFERENCES platform_feedback (id) ON DELETE CASCADE');
        }

        Schema::table('testimonials', function (Blueprint $table): void {
            $table->dropUnique('testimonials_submission_token_unique');
            $table->dropColumn(['submission_token', 'consent_given', 'show_role', 'consented_at', 'consent_withdrawn_at']);
        });
    }
};
