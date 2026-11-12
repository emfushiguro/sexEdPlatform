<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class TestModulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Teenager Module (10-17 years old)
        Module::create([
            'title' => 'Understanding Your Body - For Teenagers',
            'description' => 'An age-appropriate introduction to body awareness, personal hygiene, and healthy boundaries for Teenagers.',
            'thumbnail' => null,
            'min_age' => 10,
            'max_age' => 17,
            'age_specific_content' => [
                'teens' => [
                    'learning_approach' => 'Interactive games, colorful illustrations, and simple language',
                    'key_topics' => ['Body parts', 'Personal hygiene', 'Good touch vs bad touch', 'Privacy'],
                    'activities' => 'Coloring sheets, matching games, simple quizzes with pictures',
                ],
            ],
            'difficulty_level' => 'beginner',
            'order' => 1,
            'duration_minutes' => 30,
            'is_published' => true,
            'is_premium' => false,
            'certificate_pass_score' => 70,
        ]);

        // Teenagers Module (10-17 years old)
        Module::create([
            'title' => 'Growing Up - Puberty and Changes',
            'description' => 'Understanding the physical and emotional changes during puberty. Learn about reproductive health, relationships, and making informed decisions.',
            'thumbnail' => null,
            'min_age' => 10,
            'max_age' => 17,
            'age_specific_content' => [
                'teens' => [
                    'learning_approach' => 'Peer-focused discussions, relatable scenarios, and scientific explanations',
                    'key_topics' => ['Puberty', 'Reproductive system', 'Healthy relationships', 'Consent', 'STI prevention'],
                    'activities' => 'Case studies, group discussions, myth-busting quizzes',
                ],
            ],
            'difficulty_level' => 'intermediate',
            'order' => 2,
            'duration_minutes' => 45,
            'is_published' => true,
            'is_premium' => false,
            'certificate_pass_score' => 75,
        ]);

        // Adults Module (18-100 years old)
        Module::create([
            'title' => 'Sexual Health and Wellness - Adult Edition',
            'description' => 'Comprehensive sexual health education covering reproductive health, family planning, healthy relationships, and disease prevention for adults.',
            'thumbnail' => null,
            'min_age' => 18,
            'max_age' => 100,
            'age_specific_content' => [
                'adults' => [
                    'learning_approach' => 'Evidence-based content, medical terminology, and detailed explanations',
                    'key_topics' => ['Reproductive health', 'Family planning', 'STI/HIV prevention', 'Sexual wellness', 'Healthy relationships', 'Consent and communication'],
                    'activities' => 'Research-based quizzes, case studies, downloadable resources',
                ],
            ],
            'difficulty_level' => 'advanced',
            'order' => 3,
            'duration_minutes' => 60,
            'is_published' => true,
            'is_premium' => true,
            'certificate_pass_score' => 80,
        ]);

        // All-Ages Basic Safety Module (10-100)
        Module::create([
            'title' => 'Personal Safety and Boundaries',
            'description' => 'Essential knowledge about personal boundaries, consent, and staying safe. Adapted content for all age groups.',
            'thumbnail' => null,
            'min_age' => 10,
            'max_age' => 100,
            'age_specific_content' => [
                'teens' => [
                    'learning_approach' => 'Discussions and real-world scenarios',
                    'focus' => 'Peer pressure, digital safety, consent in relationships',
                ],
                'adults' => [
                    'learning_approach' => 'Comprehensive analysis and legal frameworks',
                    'focus' => 'Workplace boundaries, healthy relationships, supporting others',
                ],
            ],
            'difficulty_level' => 'beginner',
            'order' => 4,
            'duration_minutes' => 40,
            'is_published' => true,
            'is_premium' => false,
            'certificate_pass_score' => 70,
        ]);

        $this->command->info('✅ Created 4 test modules with age ranges');
        $this->command->info('   - Teenager Module (10-17): Understanding Your Body');
        $this->command->info('   - Teenager Module (10-17): Growing Up - Puberty and Changes');
        $this->command->info('   - Adult Module (18-100): Sexual Health and Wellness (Premium)');
        $this->command->info('   - All Ages (10-100): Personal Safety and Boundaries');
    }
}
