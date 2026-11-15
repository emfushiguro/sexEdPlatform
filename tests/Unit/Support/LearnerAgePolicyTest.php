<?php

namespace Tests\Unit\Support;

use App\Support\LearnerAgePolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LearnerAgePolicyTest extends TestCase
{
    private ?\Illuminate\Container\Container $previousContainer = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousContainer = \Illuminate\Container\Container::getInstance();
        $container = new \Illuminate\Container\Container;
        $container->instance('config', new \Illuminate\Config\Repository(['app' => ['timezone' => 'Asia/Manila']]));
        \Illuminate\Container\Container::setInstance($container);
    }

    protected function tearDown(): void
    {
        \Illuminate\Container\Container::setInstance($this->previousContainer);
        parent::tearDown();
    }

    #[DataProvider('classifications')]
    public function test_it_classifies_exact_policy_boundaries(
        ?string $birthdate,
        string $asOf,
        string $expected,
        ?string $category,
    ): void {
        config(['app.timezone' => 'Asia/Manila']);

        $this->assertSame($expected, LearnerAgePolicy::classify($birthdate, $asOf));
        $this->assertSame($category, LearnerAgePolicy::category($birthdate, $asOf));
    }

    public static function classifications(): array
    {
        return [
            'age 9' => ['2017-11-13', '2026-11-12', LearnerAgePolicy::ELIGIBLE_TEENAGER, null],
            'age 10' => ['2016-11-12', '2026-11-12', LearnerAgePolicy::ELIGIBLE_TEENAGER, LearnerAgePolicy::CATEGORY_TEENAGERS],
            'age 17' => ['2009-11-12', '2026-11-12', LearnerAgePolicy::ELIGIBLE_TEENAGER, LearnerAgePolicy::CATEGORY_TEENAGERS],
            'age 18' => ['2008-11-12', '2026-11-12', LearnerAgePolicy::ELIGIBLE_ADULT, LearnerAgePolicy::CATEGORY_ADULTS],
            'age 100' => ['1926-11-12', '2026-11-12', LearnerAgePolicy::ELIGIBLE_ADULT, LearnerAgePolicy::CATEGORY_ADULTS],
            'age 101' => ['1925-11-12', '2026-11-12', LearnerAgePolicy::ELIGIBLE_ADULT, LearnerAgePolicy::CATEGORY_ADULTS],
            'future DOB' => ['2026-11-13', '2026-11-12', LearnerAgePolicy::UNCLASSIFIED, null],
            'missing DOB' => [null, '2026-11-12', LearnerAgePolicy::UNCLASSIFIED, null],
            'invalid DOB' => ['not-a-date', '2026-11-12', LearnerAgePolicy::UNCLASSIFIED, null],
            'leap birthday before anniversary' => ['2016-02-29', '2026-02-28', LearnerAgePolicy::ELIGIBLE_TEENAGER, null],
            'leap birthday after anniversary' => ['2016-02-29', '2026-03-01', LearnerAgePolicy::ELIGIBLE_TEENAGER, LearnerAgePolicy::CATEGORY_TEENAGERS],
        ];
    }

    public function test_it_exposes_only_active_categories_and_read_aliases(): void
    {
        $this->assertSame(['teens', 'adults'], LearnerAgePolicy::activeCategories());
        $this->assertSame('teens', LearnerAgePolicy::normalizeActiveCategory('teen'));
        $this->assertSame('adults', LearnerAgePolicy::normalizeActiveCategory('adult'));
        $this->assertNull(LearnerAgePolicy::normalizeActiveCategory('kids'));
    }
}
