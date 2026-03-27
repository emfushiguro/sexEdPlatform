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
        // Kids Module (5-12 years old)
        Module::create([
            'title' => 'Understanding Your Body - For Kids',
            'description' => 'A fun and age-appropriate introduction to basic body awareness, personal hygiene, and understanding the differences between boys and girls. Perfect for young learners!',
            'thumbnail' => null,
            'min_age' => 5,
            'max_age' => 12,
            'age_specific_content' => [
                'kids' => [
                    'learning_approach' => 'Interactive games, colorful illustrations, and simple language',
                    'key_topics' => ['Body parts', 'Personal hygiene', 'Good touch vs bad touch', 'Privacy'],
                    'activities' => 'Coloring sheets, matching games, simple quizzes with pictures'
                ]
            ],
            'difficulty_level' => 'beginner',
            'order' => 1,
            'duration_minutes' => 30,
            'is_published' => true,
            'is_premium' => false,
            'certificate_pass_score' => 70,
        ]);

        // Teens Module (13-17 years old)
        Module::create([
            'title' => 'Growing Up - Puberty and Changes',
            'description' => 'Understanding the physical and emotional changes during puberty. Learn about reproductive health, relationships, and making informed decisions.',
            'thumbnail' => null,
            'min_age' => 13,
            'max_age' => 17,
            'age_specific_content' => [
                'teens' => [
                    'learning_approach' => 'Peer-focused discussions, relatable scenarios, and scientific explanations',
                    'key_topics' => ['Puberty', 'Reproductive system', 'Healthy relationships', 'Consent', 'STI prevention'],
                    'activities' => 'Case studies, group discussions, myth-busting quizzes'
                ]
            ],
            'difficulty_level' => 'intermediate',
            'order' => 2,
            'duration_minutes' => 45,
            'is_published' => true,
            'is_premium' => false,
            'certificate_pass_score' => 75,
        ]);

        // Adults Module (18+ years old)
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
                    'activities' => 'Research-based quizzes, case studies, downloadable resources'
                ]
            ],
            'difficulty_level' => 'advanced',
            'order' => 3,
            'duration_minutes' => 60,
            'is_published' => true,
            'is_premium' => true,
            'certificate_pass_score' => 80,
        ]);

        // All-Ages Basic Safety Module (5-100)
        Module::create([
            'title' => 'Personal Safety and Boundaries',
            'description' => 'Essential knowledge about personal boundaries, consent, and staying safe. Adapted content for all age groups.',
            'thumbnail' => null,
            'min_age' => 5,
            'max_age' => 100,
            'age_specific_content' => [
                'kids' => [
                    'learning_approach' => 'Simple stories and role-play scenarios',
                    'focus' => 'Body autonomy, saying no, trusted adults'
                ],
                'teens' => [
                    'learning_approach' => 'Discussions and real-world scenarios',
                    'focus' => 'Peer pressure, digital safety, consent in relationships'
                ],
                'adults' => [
                    'learning_approach' => 'Comprehensive analysis and legal frameworks',
                    'focus' => 'Workplace boundaries, healthy relationships, supporting others'
                ]
            ],
            'difficulty_level' => 'beginner',
            'order' => 4,
            'duration_minutes' => 40,
            'is_published' => true,
            'is_premium' => false,
            'certificate_pass_score' => 70,
        ]);

        $this->command->info('✅ Created 4 test modules with age ranges');
        $this->command->info('   - Kids Module (5-12): Understanding Your Body');
        $this->command->info('   - Teens Module (13-17): Growing Up - Puberty and Changes');
        $this->command->info('   - Adults Module (18+): Sexual Health and Wellness (Premium)');
        $this->command->info('   - All Ages (5-100): Personal Safety and Boundaries');
    }
}
