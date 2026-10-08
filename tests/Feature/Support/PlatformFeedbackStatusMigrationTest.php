<?php

namespace Tests\Feature\Support;

use App\Models\PlatformFeedback;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlatformFeedbackStatusMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_ticket_and_history_statuses_are_normalized(): void
    {
        $planned = PlatformFeedback::factory()->create();
        $archived = PlatformFeedback::factory()->create();
        DB::table('platform_feedback')->where('id', $planned->id)->update(['status' => 'planned']);
        DB::table('platform_feedback')->where('id', $archived->id)->update(['status' => 'archived']);
        DB::table('platform_feedback_histories')->insert([
            [
                'platform_feedback_id' => $planned->id,
                'actor_id' => null,
                'from_status' => 'new',
                'to_status' => 'planned',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'platform_feedback_id' => $archived->id,
                'actor_id' => null,
                'from_status' => 'archived',
                'to_status' => 'closed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $path = database_path('migrations/2026_09_14_000001_normalize_platform_feedback_statuses.php');
        $this->assertFileExists($path);
        (require $path)->up();

        $this->assertDatabaseHas('platform_feedback', ['id' => $planned->id, 'status' => 'reviewed']);
        $this->assertDatabaseHas('platform_feedback', ['id' => $archived->id, 'status' => 'closed']);
        $this->assertDatabaseHas('platform_feedback_histories', [
            'platform_feedback_id' => $planned->id,
            'from_status' => 'new',
            'to_status' => 'reviewed',
        ]);
        $this->assertDatabaseHas('platform_feedback_histories', [
            'platform_feedback_id' => $archived->id,
            'from_status' => 'closed',
            'to_status' => 'closed',
        ]);
    }
}
