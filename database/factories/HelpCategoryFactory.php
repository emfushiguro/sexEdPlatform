<?php

namespace Database\Factories;

use App\Models\HelpCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<HelpCategory> */
class HelpCategoryFactory extends Factory
{
    protected $model = HelpCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucwords($name),
            'slug' => str($name)->slug()->toString(),
            'description' => fake()->sentence(),
            'icon_key' => 'tools',
            'audiences' => ['all'],
            'sort_order' => fake()->numberBetween(0, 20),
            'is_active' => true,
        ];
    }
}
