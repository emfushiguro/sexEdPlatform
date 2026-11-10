<?php

namespace Tests;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Concerns\InteractsWithAdminMfa;

abstract class DatabaseTestCase extends BaseTestCase
{
    use InteractsWithAdminMfa;

    protected bool $seed = true;

    protected string $seeder = RolePermissionSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
