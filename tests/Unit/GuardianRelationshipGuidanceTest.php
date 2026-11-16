<?php

namespace Tests\Unit;

use App\Support\GuardianRelationshipTypes;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Blade;

class GuardianRelationshipGuidanceTest extends TestCase
{
    public function test_every_selectable_relationship_has_specific_guidance_with_official_https_sources(): void
    {
        foreach (GuardianRelationshipTypes::selectableValues() as $type) {
            $key = $type === 'foster_parent' ? $type : GuardianRelationshipTypes::pathway($type);
            $guide = config('guardian_relationship_guidance.'.$key);
            $this->assertNotEmpty($guide['note'], $type);
            foreach ($guide['sources'] as $url) {
                $this->assertSame('https', parse_url($url, PHP_URL_SCHEME));
                $this->assertTrue(str_ends_with(parse_url($url, PHP_URL_HOST), '.gov.ph'));
            }
            $html = Blade::render('<x-guardian-relationship-guidance :relationship-type="$type" />', ['type' => $type]);
            $this->assertStringContainsString('not an exhaustive list or automatic approval', $html);
            $this->assertStringContainsString('optional information sharing remain separate', $html);
        }
    }

    public function test_foster_specific_guidance_does_not_leak_into_other_relative_guidance(): void
    {
        $foster = Blade::render('<x-guardian-relationship-guidance relationship-type="foster_parent" />');
        $relative = Blade::render('<x-guardian-relationship-guidance relationship-type="grandmother" />');
        $this->assertStringContainsString('Foster Placement Authority', $foster);
        $this->assertStringNotContainsString('Foster Placement Authority', $relative);
        $this->assertStringContainsString('family connection alone does not establish', $relative);
    }
}
