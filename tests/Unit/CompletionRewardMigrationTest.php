<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CompletionRewardMigrationTest extends TestCase
{
    public function test_migration_preserves_old_completions_without_altering_progress(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Schema::create('users', function (Blueprint $t) {
            $t->id();
        });
        Schema::create('lesson_topic_progress', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('lesson_topic_id');
            $t->boolean('completed');
        });
        Schema::create('user_progress', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('lesson_id')->nullable();
            $t->boolean('completed');
            $t->timestamp('completed_at')->nullable();
        });
        DB::table('users')->insert(['id' => 1]);
        DB::table('lesson_topic_progress')->insert([
            ['id' => 1, 'user_id' => 1, 'lesson_topic_id' => 2, 'completed' => true],
            ['id' => 2, 'user_id' => 1, 'lesson_topic_id' => 3, 'completed' => false],
        ]);
        DB::table('user_progress')->insert([
            ['id' => 1, 'user_id' => 1, 'lesson_id' => 4, 'completed' => true, 'completed_at' => now()],
            ['id' => 2, 'user_id' => 1, 'lesson_id' => 5, 'completed' => false, 'completed_at' => null],
            ['id' => 3, 'user_id' => 1, 'lesson_id' => null, 'completed' => true, 'completed_at' => now()],
        ]);
        $before = [DB::table('lesson_topic_progress')->get()->toJson(), DB::table('user_progress')->get()->toJson()];
        $migration = require base_path('database/migrations/2026_11_15_000002_create_learning_completion_rewards.php');
        $migration->up();
        $this->assertSame(3, DB::table('learning_completion_rewards')->count());
        $this->assertSame(['topic_complete', 'topic_complete', 'lesson_complete'], DB::table('learning_completion_rewards')->orderBy('id')->pluck('reason')->all());
        $this->assertSame([2, 3, 4], DB::table('learning_completion_rewards')->orderBy('id')->pluck('subject_id')->all());
        $this->assertSame($before, [DB::table('lesson_topic_progress')->get()->toJson(), DB::table('user_progress')->get()->toJson()]);
    }
}
