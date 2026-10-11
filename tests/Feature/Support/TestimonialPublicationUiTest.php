<?php

namespace Tests\Feature\Support;

use App\Enums\TestimonialStatus;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseTestCase;

class TestimonialPublicationUiTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_independent_consented_testimonial_can_be_published_by_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $author = User::factory()->create([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
            'age' => 25,
            'birthdate' => now()->subYears(25),
            'account_type' => 'learner-adult',
        ]);
        $author->assignRole('learner');
        $testimonial = Testimonial::factory()->for($author)->create([
            'status' => TestimonialStatus::Draft,
            'display_name' => 'A learner',
            'quotation' => 'The platform made the lessons easier to understand.',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.testimonials.index'))
            ->post(route('admin.testimonials.publish', $testimonial))
            ->assertRedirect(route('admin.testimonials.index'))
            ->assertSessionHas('success', 'Testimonial published.');

        $this->assertSame(TestimonialStatus::Published, $testimonial->fresh()->status);

        $this->get(route('admin.testimonials.index'))
            ->assertOk()
            ->assertSee('Consent active')
            ->assertSee('A learner');
    }

    public function test_admin_withdrawal_keeps_user_consent_and_owner_can_revoke_published_consent(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $author = User::factory()->create([
            'role' => 'instructor',
            'account_type' => 'instructor',
            'age' => 30,
            'birthdate' => now()->subYears(30),
        ]);
        $author->assignRole('instructor');
        $testimonial = Testimonial::factory()->for($author)->create([
            'status' => TestimonialStatus::Published,
            'published_at' => now(),
            'display_role' => null,
            'show_role' => false,
            'quotation' => 'A published instructor experience.',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.testimonials.withdraw', $testimonial))
            ->assertRedirect();
        $this->assertSame(TestimonialStatus::Withdrawn, $testimonial->fresh()->status);
        $this->assertTrue((bool) $testimonial->fresh()->consent_given);
        $this->assertNull($testimonial->fresh()->consent_withdrawn_at);

        $published = Testimonial::factory()->for($author)->create([
            'status' => TestimonialStatus::Published,
            'published_at' => now(),
            'display_role' => null,
            'show_role' => false,
            'quotation' => 'A second published instructor experience.',
        ]);
        $this->actingAs($author)
            ->delete(route('testimonials.withdraw', $published))
            ->assertRedirect();
        $this->assertNotNull($published->fresh()->consent_withdrawn_at);
        $this->assertSame([], Testimonial::query()->publiclyVisible()->pluck('id')->all());
    }

    public function test_rejected_independent_testimonial_is_not_public(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $author = User::factory()->create([
            'role' => 'learner',
            'account_type' => 'learner-adult',
            'age' => 25,
            'birthdate' => now()->subYears(25),
        ]);
        $author->assignRole('learner');
        $testimonial = Testimonial::factory()->for($author)->create(['status' => TestimonialStatus::Draft]);

        $this->actingAs($admin)
            ->post(route('admin.testimonials.reject', $testimonial))
            ->assertRedirect()
            ->assertSessionHas('success', 'Testimonial rejected.');

        $this->assertSame(TestimonialStatus::Rejected, $testimonial->fresh()->status);
        $this->assertSame([], Testimonial::query()->publiclyVisible()->pluck('id')->all());
    }

    public function test_consented_profile_image_without_source_uses_initial_fallback_in_admin_and_landing(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $author = User::factory()->create([
            'role' => 'learner',
            'account_type' => 'learner-adult',
            'age' => 25,
            'birthdate' => now()->subYears(25),
        ]);
        $author->assignRole('learner');
        $testimonial = Testimonial::factory()->for($author)->create([
            'status' => TestimonialStatus::Draft,
            'published_at' => null,
            'display_name' => 'A Learner',
            'show_profile_image' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.testimonials.publish', $testimonial))
            ->assertRedirect()
            ->assertSessionHas('success', 'Testimonial published.');

        $this->actingAs($admin)
            ->get(route('admin.testimonials.preview', $testimonial))
            ->assertOk()
            ->assertSee('data-testimonial-avatar-fallback', false)
            ->assertSee('AL', false);

        auth()->logout();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-testimonial-avatar-fallback', false)
            ->assertSee('AL', false);
    }

    public function test_testimonial_management_uses_sidebar_only_navigation_and_icon_actions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $author = User::factory()->create([
            'role' => 'learner',
            'account_type' => 'learner-adult',
            'age' => 25,
            'birthdate' => now()->subYears(25),
        ]);
        $author->assignRole('learner');
        $testimonial = Testimonial::factory()->for($author)->create(['status' => TestimonialStatus::Draft]);

        $this->actingAs($admin)
            ->get(route('admin.testimonials.index'))
            ->assertOk()
            ->assertDontSee('aria-label="Support management"', false)
            ->assertSee('data-testimonial-action="preview"', false)
            ->assertSee('aria-label="Preview testimonial"', false)
            ->assertDontSee('data-testimonial-action="edit"', false)
            ->assertDontSee('data-testimonial-action="publish"', false)
            ->assertDontSee('data-testimonial-action="reject"', false)
            ->assertDontSee('data-testimonial-action="withdraw"', false)
            ->assertSee(route('admin.testimonials.preview', $testimonial), false);

        $this->actingAs($admin)
            ->get(route('admin.testimonials.preview', $testimonial))
            ->assertOk()
            ->assertSee('data-testimonial-action="edit"', false)
            ->assertSee('data-testimonial-action="publish"', false)
            ->assertSee('data-testimonial-action="reject"', false)
            ->assertSee('data-testimonial-action="withdraw"', false)
            ->assertSee('data-confirm-submit', false)
            ->assertSee('Confirm publish testimonial?', false)
            ->assertSee('Confirm reject testimonial?', false)
            ->assertSee('Confirm withdraw testimonial?', false);
    }

    public function test_submission_uses_the_learner_username_and_hides_name_input(): void
    {
        $author = User::factory()->create([
            'role' => 'learner',
            'account_type' => User::ACCOUNT_TYPE_LEARNER_ADULT,
            'age' => 25,
            'birthdate' => now()->subYears(25),
        ]);
        $author->assignRole('learner');
        $author->learnerProfile()->create(['username' => 'quiet-orchid']);

        $this->actingAs($author)->get(route('testimonials.create'))
            ->assertOk()
            ->assertSee('quiet-orchid')
            ->assertDontSee('name="display_name"', false);

        $this->actingAs($author)->post(route('testimonials.store'), [
            'submission_token' => (string) \Illuminate\Support\Str::uuid(),
            'quotation' => 'The lessons helped me feel prepared.',
            'consent' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('testimonials', ['user_id' => $author->id, 'display_name' => 'quiet-orchid']);
    }

    public function test_submission_defaults_to_showing_role_and_profile_and_admin_list_renders_profile(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $author = User::factory()->create([
            'role' => 'learner',
            'account_type' => User::ACCOUNT_TYPE_LEARNER_ADULT,
            'age' => 25,
            'birthdate' => now()->subYears(25),
        ]);
        $author->assignRole('learner');
        $author->learnerProfile()->create(['username' => 'quiet-orchid']);

        $this->actingAs($author)
            ->get(route('testimonials.create'))
            ->assertOk()
            ->assertDontSee('name="show_role"', false)
            ->assertDontSee('name="show_profile_image"', false)
            ->assertSee('displayed with your quotation');

        $this->actingAs($author)->post(route('testimonials.store'), [
            'submission_token' => (string) \Illuminate\Support\Str::uuid(),
            'quotation' => 'The lessons helped me feel prepared.',
            'consent' => 1,
        ])->assertRedirect();

        $testimonial = Testimonial::query()->where('user_id', $author->id)->latest('id')->firstOrFail();
        $this->assertTrue((bool) $testimonial->show_role);
        $this->assertTrue((bool) $testimonial->show_profile_image);
        $this->assertSame('Learner', $testimonial->display_role);

        $this->actingAs($admin)
            ->get(route('admin.testimonials.index'))
            ->assertOk()
            ->assertSee('data-testimonial-avatar-fallback', false)
            ->assertSee('quiet-orchid');
    }

    public function test_active_instructor_can_submit_a_testimonial_without_learner_age_fields(): void
    {
        $instructor = User::factory()->create([
            'name' => 'Instructor Voice',
            'role' => 'instructor',
            'account_type' => User::ACCOUNT_TYPE_INSTRUCTOR,
            'status' => User::STATUS_ACTIVE,
            'age' => null,
            'birthdate' => null,
        ]);
        $instructor->assignRole('instructor');

        $this->actingAs($instructor)
            ->get(route('testimonials.create'))
            ->assertOk()
            ->assertSee('Share Your Experience');

        $this->get(route('help.index'))
            ->assertOk()
            ->assertSee('Share Your Experience');

        $this->post(route('testimonials.store'), [
            'submission_token' => (string) \Illuminate\Support\Str::uuid(),
            'quotation' => 'Teaching here helped me grow alongside my learners.',
            'consent' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('testimonials', [
            'user_id' => $instructor->id,
            'display_name' => 'Instructor Voice',
            'display_role' => 'Instructor',
        ]);
    }

    public function test_published_instructor_testimonial_without_age_fields_is_visible_on_landing(): void
    {
        $instructor = User::factory()->create([
            'name' => 'Instructor Voice',
            'role' => 'instructor',
            'account_type' => User::ACCOUNT_TYPE_INSTRUCTOR,
            'status' => User::STATUS_ACTIVE,
            'age' => null,
            'birthdate' => null,
        ]);
        $instructor->assignRole('instructor');
        Testimonial::factory()->for($instructor)->create([
            'display_name' => 'Instructor Voice',
            'display_role' => 'Instructor',
            'show_role' => true,
            'status' => TestimonialStatus::Published,
            'published_at' => now(),
            'quotation' => 'Teaching here helped me grow alongside my learners.',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Teaching here helped me grow alongside my learners.')
            ->assertSee('Instructor Voice');
    }

    public function test_admin_testimonial_list_shows_sender_profile_for_legacy_visibility_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $author = User::factory()->create([
            'name' => 'Legacy Author',
            'role' => 'learner',
            'account_type' => User::ACCOUNT_TYPE_LEARNER_ADULT,
            'age' => 25,
            'birthdate' => now()->subYears(25),
        ]);
        $author->assignRole('learner');
        Testimonial::factory()->for($author)->create([
            'show_profile_image' => false,
            'display_name' => 'Legacy Author',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.testimonials.index'))
            ->assertOk()
            ->assertSee('data-testimonial-avatar-fallback', false)
            ->assertSee('Legacy Author');
    }

    public function test_admin_cannot_change_the_identity_snapshot_and_ineligible_accounts_are_not_public(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $author = User::factory()->create([
            'role' => 'learner',
            'account_type' => User::ACCOUNT_TYPE_LEARNER_ADULT,
            'age' => 25,
            'birthdate' => now()->subYears(25),
            'status' => User::STATUS_ACTIVE,
        ]);
        $author->assignRole('learner');
        $testimonial = Testimonial::factory()->for($author)->create(['display_name' => 'quiet-orchid']);

        $this->actingAs($admin)->put(route('admin.testimonials.update', $testimonial), [
            'display_name' => 'Not the username',
            'display_role' => 'Learner',
            'quotation' => $testimonial->quotation,
            'sort_order' => 0,
        ])->assertRedirect();
        $this->assertSame('quiet-orchid', $testimonial->fresh()->display_name);

        $author->update(['status' => User::STATUS_SUSPENDED]);
        $testimonial->update(['status' => TestimonialStatus::Published, 'published_at' => now()]);
        $this->assertSame([], Testimonial::query()->publiclyVisible()->pluck('id')->all());
    }

    public function test_consented_profile_image_uses_a_storage_backed_preview_endpoint_for_admin_and_landing(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $author = User::factory()->create([
            'role' => 'learner',
            'account_type' => User::ACCOUNT_TYPE_LEARNER_ADULT,
            'age' => 25,
            'birthdate' => now()->subYears(25),
            'status' => User::STATUS_ACTIVE,
        ]);
        $author->assignRole('learner');
        $author->learnerProfile()->create(['username' => 'quiet-orchid', 'avatar_path' => 'avatars/quiet-orchid.png']);
        Storage::disk('public')->put('avatars/quiet-orchid.png', 'png bytes');
        $testimonial = Testimonial::factory()->for($author)->create([
            'status' => TestimonialStatus::Published,
            'published_at' => now(),
            'show_profile_image' => true,
        ]);

        $this->actingAs($admin)->get(route('admin.testimonials.preview', $testimonial))
            ->assertOk()->assertSee(route('admin.testimonials.avatar', $testimonial), false);
        auth()->logout();
        $this->get(route('home'))->assertOk()->assertSee(route('testimonials.avatar', $testimonial), false);
        $this->get(route('testimonials.avatar', $testimonial))
            ->assertOk()->assertHeader('Cache-Control', 'max-age=3600, public');
    }
}
