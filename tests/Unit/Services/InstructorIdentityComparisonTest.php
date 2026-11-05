<?php

namespace Tests\Unit\Services;

use App\Services\InstructorApplication\InstructorIdentityComparison;
use Tests\TestCase;

class InstructorIdentityComparisonTest extends TestCase
{
    public function test_exact_and_normalized_values_match_without_returning_raw_values(): void
    {
        $summary = app(InstructorIdentityComparison::class)->compare(
            [
                'first_name' => ' Ana-Maria ',
                'middle_initial' => ' B. ',
                'last_name' => 'Dela Cruz',
                'birthdate' => '1990-01-02',
                'city_name' => 'Cavite City',
                'barangay_name' => 'Barangay Test',
            ],
            [
                'name-first' => 'ana maria',
                'name-middle' => 'b',
                'name-last' => 'dela cruz',
                'birthdate' => '1990-01-02',
                'city' => 'CAVITE CITY',
                'subdivision' => 'barangay test',
                'document-number' => 'must-not-be-returned',
            ],
        );

        $this->assertSame('v1', $summary['normalization_version']);
        $this->assertSame('match', $summary['fields']['first_name']['outcome']);
        $this->assertSame('match', $summary['fields']['middle_initial']['outcome']);
        $this->assertSame('match', $summary['fields']['last_name']['outcome']);
        $this->assertSame('match', $summary['fields']['birthdate']['outcome']);
        $this->assertSame('match', $summary['fields']['city_name']['outcome']);
        $this->assertSame('match', $summary['fields']['barangay_name']['outcome']);
        $this->assertSame('not_comparable', $summary['fields']['suffix']['outcome']);
        $this->assertStringNotContainsString('Ana', json_encode($summary));
        $this->assertStringNotContainsString('must-not-be-returned', json_encode($summary));
    }

    public function test_differences_and_missing_values_are_explicit(): void
    {
        $summary = app(InstructorIdentityComparison::class)->compare(
            [
                'first_name' => 'Ana',
                'last_name' => 'Applicant',
                'birthdate' => '1990-01-02',
                'city_name' => 'Cavite City',
                'barangay_name' => null,
            ],
            [
                'name-first' => 'Different',
                'name-last' => null,
                'birthdate' => '1990-01-03',
                'city' => null,
                'subdivision' => 'Barangay Test',
            ],
        );

        $this->assertSame('mismatch', $summary['fields']['first_name']['outcome']);
        $this->assertSame('missing_from_persona', $summary['fields']['last_name']['outcome']);
        $this->assertSame('mismatch', $summary['fields']['birthdate']['outcome']);
        $this->assertSame('missing_from_persona', $summary['fields']['city_name']['outcome']);
        $this->assertSame('missing_from_applicant', $summary['fields']['barangay_name']['outcome']);
    }
}
