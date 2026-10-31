<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE instructor_applications MODIFY COLUMN clearance_path VARCHAR(255) NULL');
        DB::statement('ALTER TABLE instructor_applications MODIFY COLUMN cv_resume_path VARCHAR(255) NULL');
    }

    public function down(): never
    {
        throw new LogicException(
            'Draft application history must not be made invalid by rollback.'
        );
    }
};
