<?php

namespace Database\Factories;

use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModuleEnrollmentFactory extends Factory
{
    protected $model = ModuleEnrollment::class;

    public function definition(): array
    {
        return [
            'user_id'               => User::factory(),
            'module_id'             => Module::factory(),
            'status'                => 'approved',
            'enrolled_at'           => now(),
            'completion_percentage' => 0,
        ];
    }
}
