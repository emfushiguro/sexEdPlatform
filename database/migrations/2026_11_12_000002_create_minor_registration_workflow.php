<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('email')->nullable()->change();
        });

        Schema::table('learner_profiles', function (Blueprint $table): void {
            $table->string('username')->nullable()->change();
        });

        foreach (['minor_registration_authorized_at', 'minor_registration_consent_version', 'minor_registration_authorized_by_user_id'] as $column) {
            if (! Schema::hasColumn('parent_child_accounts', $column)) {
                Schema::table('parent_child_accounts', function (Blueprint $table) use ($column): void {
                    match ($column) {
                        'minor_registration_authorized_at' => $table->timestamp($column)->nullable(),
                        'minor_registration_consent_version' => $table->string($column, 32)->nullable(),
                        default => $table->foreignId($column)->nullable(),
                    };
                });
            }
        }

        $hasAuthorizerForeignKey = collect(Schema::getForeignKeys('parent_child_accounts'))
            ->contains(fn (array $key): bool => $key['columns'] === ['minor_registration_authorized_by_user_id']);

        if (! $hasAuthorizerForeignKey) {
            Schema::table('parent_child_accounts', function (Blueprint $table): void {
                $table->foreign('minor_registration_authorized_by_user_id', 'pca_minor_authorizer_fk')
                    ->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('minor_registration_requests')) {
            Schema::create('minor_registration_requests', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('existing_child_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('guardian_email');
                $table->char('guardian_email_fingerprint', 64)->index();
                $table->text('minor_payload')->nullable();
                $table->char('token_hash', 64)->unique();
                $table->string('status', 24)->default('pending')->index();
                $table->timestamp('expires_at')->index();
                $table->foreignId('accepted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('minor_account_handoffs')) {
            Schema::create('minor_account_handoffs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('child_user_id')->constrained('users')->restrictOnDelete();
                $table->foreignId('guardian_user_id')->constrained('users')->restrictOnDelete();
                $table->foreignId('parent_child_account_id')->constrained('parent_child_accounts')->restrictOnDelete();
                $table->string('purpose', 24)->index();
                $table->char('token_hash', 64)->nullable()->unique();
                $table->timestamp('approved_at')->nullable();
                $table->dateTime('expires_at')->index();
                $table->timestamp('used_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Forward-only compatibility migration; existing data must remain intact.
    }
};
