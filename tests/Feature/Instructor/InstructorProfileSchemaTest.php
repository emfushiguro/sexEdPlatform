<?php

namespace Tests\Feature\Instructor;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InstructorProfileSchemaTest extends TestCase
{
    public function test_instructor_profiles_table_has_professional_identity_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('instructor_profiles', [
            'educational_background',
            'professional_background',
            'primary_expertise',
            'expertise_tags',
            'years_experience',
            'certifications',
            'profile_photo_path',
        ]));
    }
}
