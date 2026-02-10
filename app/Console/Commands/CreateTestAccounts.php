<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateTestAccounts extends Command
{
    protected $signature = 'app:create-test-accounts';
    protected $description = 'Create test instructor and learner accounts';

    public function handle()
    {
        // Ensure learner role exists
        $learnerRole = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'learner',
            'guard_name' => 'web'
        ]);
        $this->info('✓ Learner role ensured');

        // Create Instructor (or skip if exists)
        $instructor = User::where('email', 'instructor@test.com')->first();
        if (!$instructor) {
            $instructor = User::create([
                'name' => 'Jane Smith',
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'instructor@test.com',
                'password' => Hash::make('password123'),
            ]);
            $instructor->assignRole('instructor');
            $this->info('✓ Instructor created: instructor@test.com');
        } else {
            $this->warn('⚠ Instructor already exists: instructor@test.com');
        }

        // Create Learner (or skip if exists)
        $learner = User::where('email', 'learner@test.com')->first();
        if (!$learner) {
            $learner = User::create([
                'name' => 'John Doe',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'learner@test.com',
                'password' => Hash::make('password123'),
            ]);
            $learner->assignRole('learner');
            $this->info('✓ Learner created: learner@test.com');
        } else {
            $this->warn('⚠ Learner already exists: learner@test.com');
        }

        $this->info('');
        $this->info('Password for both: password123');
        $this->info('');
        $this->info('🎉 Test accounts ready!');

        return 0;
    }
}
