<?php

namespace Tests\Unit\Support;

use App\Support\ConnectorVerificationRequirements;
use Tests\TestCase;

class ConnectorVerificationRequirementsTest extends TestCase
{
    public function test_every_organization_type_resolves_complete_safe_requirements(): void
    {
        $registry = app(ConnectorVerificationRequirements::class);

        $this->assertCount(20, $registry->organizationTypes());

        foreach (array_keys($registry->organizationTypes()) as $type) {
            $category = $this->compatibleCategoryFor($registry, $type);
            $requirements = collect($registry->requirementsFor($category, $type));

            $this->assertSame(
                $requirements->count(),
                $requirements->pluck('key')->unique()->count(),
                "Requirement keys must be unique for {$type}."
            );
            $this->assertTrue($requirements->contains('evidence_category', 'representative_identity'));
            $this->assertTrue($requirements->contains('evidence_category', 'representative_authority'));
            $this->assertTrue($requirements->every(
                static fn (array $item): bool => $item['max_kilobytes'] <= 10240
                    && count(array_diff($item['accepted_mime_types'], [
                        'application/pdf', 'image/jpeg', 'image/png', 'image/webp',
                    ])) === 0
            ));

            foreach ($requirements as $requirement) {
                if ($requirement['source_url'] !== null) {
                    $this->assertNotEmpty($requirement['issuing_authority']);
                    $this->assertStringStartsWith('https://', $requirement['source_url']);
                    $this->assertNotEmpty($requirement['source_reviewed_on']);
                }
            }
        }
    }

    public function test_category_compatibility_is_explicit_and_snapshots_are_plain_arrays(): void
    {
        $registry = app(ConnectorVerificationRequirements::class);
        $categories = [
            'government',
            'ngo',
            'community_based_organization',
            'school_educational_institution',
            'health_organization',
            'advocacy_group',
            'other',
        ];

        foreach (array_keys($registry->organizationTypes()) as $type) {
            $allowed = $registry->organizationTypes()[$type]['categories'];

            foreach ($categories as $category) {
                $this->assertSame(in_array($category, $allowed, true), $registry->isCompatible($category, $type));
            }

            $this->assertIsArray($registry->snapshotFor($this->compatibleCategoryFor($registry, $type), $type));
        }
    }

    private function compatibleCategoryFor(ConnectorVerificationRequirements $registry, string $type): string
    {
        return $registry->organizationTypes()[$type]['categories'][0];
    }
}
