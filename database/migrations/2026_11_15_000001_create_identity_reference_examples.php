<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('identity_reference_examples', function (Blueprint $table): void {
            $table->id();
            $table->string('id_type', 80)->index();
            $table->string('issuer', 120);
            $table->string('version', 120);
            $table->date('published_on');
            $table->text('source_url');
            $table->text('verification_url')->nullable();
            $table->string('image_path');
            $table->string('image_mime', 40);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('identity_reference_examples'); }
};
