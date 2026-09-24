<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('learning_paths')) {
            Schema::create('learning_paths', function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->text('description');
                $table->string('thumbnail')->nullable();
                $table->string('status', 20)->default('draft')->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('learning_path_modules')) {
            Schema::create('learning_path_modules', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('learning_path_id')->constrained()->cascadeOnDelete();
                $table->foreignId('module_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('position');
                $table->timestamps();
                $table->unique(['learning_path_id', 'module_id']);
                $table->unique(['learning_path_id', 'position']);
                $table->index(['module_id', 'learning_path_id']);
            });
        }

        if (!Schema::hasTable('learning_path_learner_categories')) {
            Schema::create('learning_path_learner_categories', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('learning_path_id')->constrained()->cascadeOnDelete();
                $table->string('category', 16);
                $table->timestamps();
                $table->unique(['learning_path_id', 'category'], 'lplc_path_category_unique');
                $table->index(['category', 'learning_path_id'], 'lplc_category_path_index');
            });
        }

        if (Schema::hasTable('learning_paths') && !Schema::hasIndex('learning_paths', 'learning_paths_status_index')) {
            Schema::table('learning_paths', function (Blueprint $table): void {
                $table->index('status', 'learning_paths_status_index');
            });
        }

        if (Schema::hasTable('learning_path_modules')) {
            if (!Schema::hasIndex('learning_path_modules', 'learning_path_modules_learning_path_id_module_id_unique')) {
                Schema::table('learning_path_modules', function (Blueprint $table): void {
                    $table->unique(
                        ['learning_path_id', 'module_id'],
                        'learning_path_modules_learning_path_id_module_id_unique',
                    );
                });
            }

            if (!Schema::hasIndex('learning_path_modules', 'learning_path_modules_learning_path_id_position_unique')) {
                Schema::table('learning_path_modules', function (Blueprint $table): void {
                    $table->unique(
                        ['learning_path_id', 'position'],
                        'learning_path_modules_learning_path_id_position_unique',
                    );
                });
            }

            if (!Schema::hasIndex('learning_path_modules', 'learning_path_modules_module_id_learning_path_id_index')) {
                Schema::table('learning_path_modules', function (Blueprint $table): void {
                    $table->index(
                        ['module_id', 'learning_path_id'],
                        'learning_path_modules_module_id_learning_path_id_index',
                    );
                });
            }
        }

        if (Schema::hasTable('learning_path_learner_categories')) {
            if (!Schema::hasIndex('learning_path_learner_categories', 'lplc_path_category_unique')) {
                Schema::table('learning_path_learner_categories', function (Blueprint $table): void {
                    $table->unique(['learning_path_id', 'category'], 'lplc_path_category_unique');
                });
            }

            if (!Schema::hasIndex('learning_path_learner_categories', 'lplc_category_path_index')) {
                Schema::table('learning_path_learner_categories', function (Blueprint $table): void {
                    $table->index(['category', 'learning_path_id'], 'lplc_category_path_index');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_path_learner_categories');
        Schema::dropIfExists('learning_path_modules');
        Schema::dropIfExists('learning_paths');
    }
};
