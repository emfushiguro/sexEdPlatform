<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Base class for unit tests that do NOT require a database.
 * Unit tests that need database should extend TestCase instead.
 */
abstract class UnitTestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
