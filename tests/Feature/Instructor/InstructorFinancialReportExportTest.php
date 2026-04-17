<?php

namespace Tests\Feature\Instructor;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorFinancialReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_can_export_own_earnings_report_as_csv(): void
    {
        /** @var User $instructor */
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        $response = $this->actingAs($instructor)
            ->get(route('instructor.earnings.export', ['format' => 'csv', 'report_type' => 'monthly']));

        $response->assertOk();
        $this->assertStringContainsString('.csv', (string) $response->headers->get('content-disposition'));
    }
}
