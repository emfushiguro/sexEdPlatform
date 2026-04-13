<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructor_application_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('instructor_application_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['approved', 'rejected']);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->longText('admin_message')->nullable();
            $table->string('reason_code')->nullable();
            $table->string('reason_label')->nullable();
            $table->text('reason_note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['instructor_application_id', 'reviewed_at'], 'iar_app_reviewed_at_idx');
            $table->index(['status', 'reviewed_at'], 'iar_status_reviewed_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_application_reviews');
    }
};
