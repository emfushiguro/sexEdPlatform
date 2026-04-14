<?php

namespace Tests\Unit\Models;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\UnitTestCase;

class UserFullNameAccessorTest extends UnitTestCase
{
    #[Test]
    public function full_name_accessor_builds_name_from_available_name_parts(): void
    {
        $user = new User([
            'first_name' => 'Juan',
            'middle_initial' => 'D',
            'last_name' => 'Dela Cruz',
            'suffix' => 'Jr',
        ]);

        $this->assertSame('Juan D. Dela Cruz Jr', $user->full_name);
    }

    #[Test]
    public function full_name_accessor_falls_back_to_name_when_name_parts_are_missing(): void
    {
        $user = new User([
            'name' => 'Admin Created Instructor',
            'first_name' => null,
            'last_name' => null,
        ]);

        $this->assertSame('Admin Created Instructor', $user->full_name);
    }
}
