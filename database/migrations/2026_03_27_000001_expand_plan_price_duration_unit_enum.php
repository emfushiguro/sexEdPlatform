<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->updateDurationUnitColumn(['minute', 'hour', 'day', 'week', 'month', 'year']);
    }

    public function down(): void
    {
        $this->updateDurationUnitColumn(['day', 'week', 'month', 'year']);
    }

    private function updateDurationUnitColumn(array $allowedValues): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $enumValues = implode("','", $allowedValues);
            DB::statement("ALTER TABLE plan_prices MODIFY COLUMN duration_unit ENUM('{$enumValues}') NOT NULL");

            return;
        }

        if ($driver !== 'sqlite') {
            return;
        }

        Schema::disableForeignKeyConstraints();

        Schema::create('plan_prices_tmp', function (Blueprint $table) use ($allowedValues) {
            $table->id();
            $table->foreignId('plan_id')->constrained('subscription_plans')->cascadeOnDelete();
            $table->enum('duration_mode', ['preset', 'custom'])->default('preset');
            $table->enum('duration_unit', $allowedValues);
            $table->unsignedInteger('duration_count');
            $table->string('duration_label');
            $table->unsignedInteger('amount_minor');
            $table->string('currency', 3)->default('PHP');
            $table->unsignedInteger('compare_at_minor')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['plan_id', 'is_active']);
            $table->index(['plan_id', 'is_default']);
        });

        DB::table('plan_prices')
            ->select([
                'id',
                'plan_id',
                'duration_mode',
                'duration_unit',
                'duration_count',
                'duration_label',
                'amount_minor',
                'currency',
                'compare_at_minor',
                'is_default',
                'is_active',
                'created_at',
                'updated_at',
            ])
            ->orderBy('id')
            ->chunk(100, function ($rows) {
                $payload = [];

                foreach ($rows as $row) {
                    $payload[] = (array) $row;
                }

                DB::table('plan_prices_tmp')->insert($payload);
            });

        Schema::drop('plan_prices');
        Schema::rename('plan_prices_tmp', 'plan_prices');
        Schema::enableForeignKeyConstraints();
    }
};
