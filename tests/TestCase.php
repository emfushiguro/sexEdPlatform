<?php

namespace Tests;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected bool $seed = true;
    protected string $seeder = RolePermissionSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
