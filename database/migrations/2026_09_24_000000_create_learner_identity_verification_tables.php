<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('learner_identity_verifications')) {
            Schema::create('learner_identity_verifications', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('pathway', 16);
                $table->string('document_type', 32)->nullable();
                $table->string('government_id_type', 40)->nullable();
                $table->string('government_id_type_other', 80)->nullable();
                $table->string('status', 16)->nullable();
                $table->unsignedInteger('submission_round')->default(0);
                $table->timestamp('submitted_at')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamp('superseded_at')->nullable();
                $table->timestamps();
            });
        }

        $this->ensureColumns('learner_identity_verifications', [
            'id', 'user_id', 'pathway', 'document_type', 'government_id_type', 'government_id_type_other',
            'status', 'submission_round', 'submitted_at', 'reviewed_by', 'reviewed_at', 'approved_at',
            'rejection_reason', 'superseded_at', 'created_at', 'updated_at',
        ]);
        $this->ensurePrimaryKey('learner_identity_verifications');
        $this->ensureUniqueIndex('learner_identity_verifications', ['user_id', 'pathway'], 'learner_idv_user_pathway_uq');
        $this->ensureIndex(
            'learner_identity_verifications',
            ['pathway', 'status', 'superseded_at'],
            'learner_idv_path_status_superseded_idx',
        );
        $this->ensureForeignKey('learner_identity_verifications', 'user_id', 'users', 'id', 'cascade');
        $this->ensureForeignKey('learner_identity_verifications', 'reviewed_by', 'users', 'id', 'set null');

        if (! Schema::hasTable('learner_identity_evidence')) {
            Schema::create('learner_identity_evidence', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('verification_id')->constrained('learner_identity_verifications')->cascadeOnDelete();
                $table->string('slot', 24);
                $table->string('storage_path');
                $table->string('mime_type', 64);
                $table->unsignedBigInteger('byte_size');
                $table->unsignedInteger('width');
                $table->unsignedInteger('height');
                $table->timestamp('submitted_at');
                $table->timestamps();
                $table->unique(['verification_id', 'slot']);
            });
        }

        $this->ensureColumns('learner_identity_evidence', [
            'id', 'verification_id', 'slot', 'storage_path', 'mime_type', 'byte_size', 'width', 'height',
            'submitted_at', 'created_at', 'updated_at',
        ]);
        $this->ensurePrimaryKey('learner_identity_evidence');
        $this->ensureUniqueIndex('learner_identity_evidence', ['verification_id', 'slot'], 'learner_idevidence_case_slot_uq');
        $this->ensureForeignKey('learner_identity_evidence', 'verification_id', 'learner_identity_verifications', 'id', 'cascade');

        if (! Schema::hasTable('learner_identity_audits')) {
            Schema::create('learner_identity_audits', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('verification_id')->constrained('learner_identity_verifications')->cascadeOnDelete();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 32);
                $table->string('from_status', 16)->nullable();
                $table->string('to_status', 16)->nullable();
                $table->unsignedInteger('submission_round');
                $table->text('reason')->nullable();
                $table->timestamp('created_at');
            });
        }

        $this->ensureColumns('learner_identity_audits', [
            'id', 'verification_id', 'actor_id', 'action', 'from_status', 'to_status', 'submission_round', 'reason', 'created_at',
        ]);
        $this->ensurePrimaryKey('learner_identity_audits');
        $this->ensureForeignKey('learner_identity_audits', 'verification_id', 'learner_identity_verifications', 'id', 'cascade');
        $this->ensureForeignKey('learner_identity_audits', 'actor_id', 'users', 'id', 'set null');
    }

    public function down(): never
    {
        throw new \LogicException('Learner identity verification records must not be removed by rollback.');
    }

    private function ensureColumns(string $table, array $columns): void
    {
        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                throw new \LogicException("Existing {$table} table is missing required column {$column}; refusing to mark migration complete.");
            }
        }
    }

    private function ensureUniqueIndex(string $table, array $columns, string $name): void
    {
        $this->ensureIndex($table, $columns, $name, true);
    }

    private function ensurePrimaryKey(string $table): void
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($index['primary']) {
                if ($index['columns'] !== ['id']) {
                    throw new \LogicException("Existing {$table} primary key is incompatible with the learner identity schema.");
                }

                return;
            }
        }

        Schema::table($table, static function (Blueprint $blueprint): void {
            $blueprint->primary('id');
        });
    }

    private function ensureIndex(string $table, array $columns, string $name, bool $unique = false): void
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($index['columns'] !== $columns) {
                continue;
            }

            if ($index['unique'] === $unique) {
                return;
            }

            if ($index['unique']) {
                throw new \LogicException("Existing {$table} index on ".implode(', ', $columns).' has incompatible uniqueness.');
            }
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $name, $unique): void {
            $unique
                ? $blueprint->unique($columns, $name)
                : $blueprint->index($columns, $name);
        });
    }

    private function ensureForeignKey(
        string $table,
        string $column,
        string $referencedTable,
        string $referencedColumn,
        string $onDelete,
    ): void {
        foreach (Schema::getForeignKeys($table) as $foreignKey) {
            if ($foreignKey['columns'] !== [$column]) {
                continue;
            }

            if (
                $foreignKey['foreign_table'] === $referencedTable
                && $foreignKey['foreign_columns'] === [$referencedColumn]
                && $foreignKey['on_delete'] === $onDelete
            ) {
                return;
            }

            throw new \LogicException("Existing {$table}.{$column} foreign key is incompatible with the learner identity schema.");
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $referencedTable, $referencedColumn, $onDelete): void {
            $blueprint->foreign($column)
                ->references($referencedColumn)
                ->on($referencedTable)
                ->onDelete($onDelete);
        });
    }
};
