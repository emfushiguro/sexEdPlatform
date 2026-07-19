<?php

namespace Tests\Feature\Seminars;

use App\Models\Connector;
use App\Models\LearnerProfile;
use App\Models\Seminar;
use App\Models\User;
use App\Notifications\Seminars\SeminarRegistrationConfirmedNotification;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class SeminarRegistrationTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_learner_sees_only_age_eligible_published_seminars(): void
    {
        $connector = $this->connector();
        $teen = $this->learnerWithAge('teens', now()->subYears(15));
        $eligible = $this->seminar($connector, ['title' => 'Teen Wellness', 'learner_age_categories' => ['teen']]);
        $ineligible = $this->seminar($connector, ['title' => 'Adult Wellness', 'learner_age_categories' => ['adult']]);

        $this->actingAs($teen)
            ->get(route('seminars.index'))
            ->assertOk()
            ->assertSee($eligible->title)
            ->assertDontSee($ineligible->title);
    }

    public function test_learner_discovery_only_shows_published_seminars(): void
    {
        $connector = $this->connector();
        $learner = $this->learnerWithAge('adults', now()->subYears(21));
        $published = $this->seminar($connector, ['title' => 'Published Session', 'status' => 'published']);

        foreach (['draft', 'pending_review', 'approved', 'rejected', 'cancelled', 'archived'] as $status) {
            $this->seminar($connector, ['title' => 'Hidden '.str_replace('_', ' ', $status), 'status' => $status]);
        }

        $this->actingAs($learner)
            ->get(route('seminars.index'))
            ->assertOk()
            ->assertSee($published->title)
            ->assertDontSee('Hidden draft')
            ->assertDontSee('Hidden pending review')
            ->assertDontSee('Hidden approved')
            ->assertDontSee('Hidden rejected')
            ->assertDontSee('Hidden cancelled')
            ->assertDontSee('Hidden archived');
    }

    public function test_learner_discovery_filters_by_type_category_and_upcoming(): void
    {
        $connector = $this->connector();
        $learner = $this->learnerWithAge('adults', now()->subYears(21));
        $matching = $this->seminar($connector, [
            'title' => 'Upcoming Health Webinar',
            'type' => 'webinar',
            'category' => 'health',
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addHour(),
            'schedule' => now()->addDays(2),
        ]);
        $this->seminar($connector, ['title' => 'Physical Health Session', 'type' => 'physical', 'category' => 'health', 'location' => 'Hall']);
        $this->seminar($connector, ['title' => 'Community Webinar', 'type' => 'webinar', 'category' => 'community']);
        $this->seminar($connector, [
            'title' => 'Past Health Webinar',
            'type' => 'webinar',
            'category' => 'health',
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDays(2)->addHour(),
            'schedule' => now()->subDays(2),
        ]);

        $this->actingAs($learner)
            ->get(route('seminars.index', ['type' => 'webinar', 'category' => 'health', 'upcoming' => '1']))
            ->assertOk()
            ->assertSee($matching->title)
            ->assertDontSee('Physical Health Session')
            ->assertDontSee('Community Webinar')
            ->assertDontSee('Past Health Webinar');
    }

    public function test_learner_discovery_respects_age_category(): void
    {
        $connector = $this->connector();
        $teen = $this->learnerWithAge('teens', now()->subYears(15));
        $this->seminar($connector, ['title' => 'Adult Only Seminar', 'learner_age_categories' => ['adult']]);
        $teenSeminar = $this->seminar($connector, ['title' => 'Teen Only Seminar', 'learner_age_categories' => ['teen']]);

        $this->actingAs($teen)
            ->get(route('seminars.index'))
            ->assertOk()
            ->assertSee($teenSeminar->title)
            ->assertDontSee('Adult Only Seminar');
    }

    public function test_custom_category_is_displayed_to_learners(): void
    {
        $connector = $this->connector();
        $learner = $this->learnerWithAge('adults', now()->subYears(21));
        $seminar = $this->seminar($connector, [
            'title' => 'Barangay Robotics Session',
            'category' => 'other',
            'custom_category' => 'Robotics',
        ]);

        $this->actingAs($learner)
            ->get(route('seminars.index'))
            ->assertOk()
            ->assertSee($seminar->title)
            ->assertSee('Robotics');

        $this->actingAs($learner)
            ->get(route('seminars.show', $seminar))
            ->assertOk()
            ->assertSee('Robotics');
    }

    public function test_instructor_sees_instructor_eligible_seminars(): void
    {
        $connector = $this->connector();
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');
        $eligible = $this->seminar($connector, ['title' => 'Instructor Webinar', 'target_participants' => 'instructors', 'learner_age_categories' => []]);
        $learnerOnly = $this->seminar($connector, ['title' => 'Learner Webinar', 'target_participants' => 'learners', 'learner_age_categories' => ['adult']]);

        $this->actingAs($instructor)
            ->get(route('seminars.index'))
            ->assertOk()
            ->assertSee($eligible->title)
            ->assertDontSee($learnerOnly->title);
    }

    public function test_eligible_learner_can_register_and_cancel_before_start(): void
    {
        Notification::fake();

        $connector = $this->connector();
        $learner = $this->learnerWithAge('adults', now()->subYears(21));
        $seminar = $this->seminar($connector, ['learner_age_categories' => ['adult']]);

        $this->actingAs($learner)
            ->post(route('seminars.register', $seminar))
            ->assertRedirect(route('seminars.show', $seminar));

        $this->assertDatabaseHas('seminar_registrants', [
            'seminar_id' => $seminar->id,
            'user_id' => $learner->id,
            'status' => 'registered',
            'participant_type' => 'learner',
        ]);
        Notification::assertSentTo($learner, SeminarRegistrationConfirmedNotification::class);

        $this->actingAs($learner)
            ->post(route('seminars.cancel-registration', $seminar))
            ->assertRedirect(route('seminars.show', $seminar));

        $this->assertDatabaseHas('seminar_registrants', [
            'seminar_id' => $seminar->id,
            'user_id' => $learner->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_duplicate_registration_is_blocked(): void
    {
        $connector = $this->connector();
        $learner = $this->learnerWithAge('adults', now()->subYears(21));
        $seminar = $this->seminar($connector, ['learner_age_categories' => ['adult']]);

        $this->actingAs($learner)->post(route('seminars.register', $seminar));

        $this->actingAs($learner)
            ->post(route('seminars.register', $seminar))
            ->assertSessionHasErrors('seminar');
    }

    public function test_capacity_and_started_registration_are_blocked(): void
    {
        $connector = $this->connector();
        $first = $this->learnerWithAge('adults', now()->subYears(21));
        $second = $this->learnerWithAge('adults', now()->subYears(22));
        $seminar = $this->seminar($connector, ['capacity' => 1, 'learner_age_categories' => ['adult']]);

        $this->actingAs($first)->post(route('seminars.register', $seminar));

        $this->actingAs($second)
            ->post(route('seminars.register', $seminar))
            ->assertSessionHasErrors('seminar');

        $started = $this->seminar($connector, [
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
            'schedule' => now()->subMinute(),
            'learner_age_categories' => ['adult'],
        ]);

        $this->actingAs($second)
            ->post(route('seminars.register', $started))
            ->assertSessionHasErrors('seminar');
    }

    private function connector(): Connector
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');

        return $this->createVerifiedConnector($owner);
    }

    private function learnerWithAge(string $bracket, \Carbon\CarbonInterface $birthdate): User
    {
        $this->seedCaviteAddress();
        $learner = User::factory()->create([
            'role' => 'learner',
            'age_bracket_cached' => $bracket,
        ]);
        $learner->assignRole('learner');

        LearnerProfile::query()->create([
            'user_id' => $learner->id,
            'username' => 'seminar_'.$learner->id,
            'birthdate' => $birthdate->toDateString(),
            'city_code' => '402101000',
            'barangay_code' => '402101001',
            'barangay' => 'Barangay Test',
        ]);

        return $learner;
    }

    private function seminar(Connector $connector, array $overrides = []): Seminar
    {
        return Seminar::query()->create(array_merge([
            'connector_id' => $connector->id,
            'type' => 'webinar',
            'title' => 'Community Wellness Webinar',
            'description' => 'A free community session.',
            'purpose' => 'Support learner wellness.',
            'category' => 'health',
            'status' => 'published',
            'schedule' => now()->addDay(),
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'capacity' => 50,
            'target_participants' => 'learners_and_instructors',
            'learner_age_categories' => ['adult'],
            'livestream_channel' => 'seminar-test-channel-'.str()->random(6),
        ], $overrides));
    }
}
