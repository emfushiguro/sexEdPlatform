<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            $table->string('learner_name_snapshot')->nullable()->after('certificate_number');
            $table->string('module_title_snapshot')->nullable()->after('learner_name_snapshot');
            $table->string('pdf_path')->nullable()->after('module_title_snapshot');
        });

        DB::table('certificates')
            ->select(['id', 'user_id', 'module_id', 'learner_name_snapshot', 'module_title_snapshot'])
            ->orderBy('id')
            ->chunkById(100, function ($certificates): void {
                foreach ($certificates as $certificate) {
                    $updates = [];

                    if (empty($certificate->learner_name_snapshot)) {
                        $learnerName = DB::table('users')->where('id', $certificate->user_id)->value('name');

                        if (!empty($learnerName)) {
                            $updates['learner_name_snapshot'] = $learnerName;
                        }
                    }

                    if (empty($certificate->module_title_snapshot)) {
                        $moduleTitle = DB::table('modules')->where('id', $certificate->module_id)->value('title');

                        if (!empty($moduleTitle)) {
                            $updates['module_title_snapshot'] = $moduleTitle;
                        }
                    }

                    if (!empty($updates)) {
                        DB::table('certificates')->where('id', $certificate->id)->update($updates);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            $table->dropColumn(['learner_name_snapshot', 'module_title_snapshot', 'pdf_path']);
        });
    }
};
