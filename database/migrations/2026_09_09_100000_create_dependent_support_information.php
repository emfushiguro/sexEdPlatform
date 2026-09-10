<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_child_accounts', function (Blueprint $table): void {
            $table->boolean('can_manage_support_information')
                ->default(false)
                ->after('can_approve_content');
        });

        Schema::create('dependent_support_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dependent_user_id')
                ->unique()
                ->constrained('users', indexName: 'dsp_profiles_dependent_fk')
                ->cascadeOnDelete();
            $table->text('relevant_health_considerations')->nullable();
            $table->text('accessibility_support_needs')->nullable();
            $table->text('additional_relevant_information')->nullable();
            $table->string('privacy_notice_version', 32);
            $table->timestamp('purpose_acknowledged_at');
            $table->foreignId('purpose_acknowledged_by_user_id')
                ->nullable()
                ->constrained('users', indexName: 'dsp_profiles_ack_by_fk')
                ->nullOnDelete();
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users', indexName: 'dsp_profiles_created_by_fk')
                ->nullOnDelete();
            $table->foreignId('updated_by_user_id')
                ->nullable()
                ->constrained('users', indexName: 'dsp_profiles_updated_by_fk')
                ->nullOnDelete();
            $table->timestamps(6);
        });

        Schema::create('dependent_support_information_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dependent_user_id')
                ->constrained('users', indexName: 'dsp_audits_dependent_fk')
                ->cascadeOnDelete();
            $table->foreignId('actor_user_id')
                ->nullable()
                ->constrained('users', indexName: 'dsp_audits_actor_fk')
                ->nullOnDelete();
            $table->foreignId('parent_child_account_id')
                ->nullable()
                ->constrained('parent_child_accounts', indexName: 'dsp_audits_relationship_fk')
                ->nullOnDelete();
            $table->string('action', 32)->index();
            $table->json('changed_fields')->nullable();
            $table->timestamp('occurred_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dependent_support_information_audits');
        Schema::dropIfExists('dependent_support_profiles');

        Schema::table('parent_child_accounts', function (Blueprint $table): void {
            $table->dropColumn('can_manage_support_information');
        });
    }
};
