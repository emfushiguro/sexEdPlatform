<?php

namespace Tests\Feature\Connectors;

use App\Enums\PaymentStatus;
use App\Models\Connector;
use App\Models\ConnectorModulePurchase;
use App\Models\ConnectorRole;
use App\Models\LearnerProfile;
use App\Models\Module;
use App\Models\ModuleMarketplaceListing;
use App\Models\Payment;
use App\Models\User;
use App\Services\Connectors\ConnectorRoleService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

trait ConnectorTestHelpers
{
    private function educationalEventPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Community learning event',
            'description' => 'A guided education session.',
            'purpose' => 'Learn practical safety skills.',
            'type' => 'webinar',
            'event_format' => 'external',
            'category' => 'health',
            'starts_at' => now()->addDays(3)->timezone(config('app.display_timezone'))->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDays(3)->addHour()->timezone(config('app.display_timezone'))->format('Y-m-d H:i:s'),
            'capacity' => 50,
            'registration_approval_mode' => 'auto_approve',
            'target_participants' => 'learners_and_instructors',
            'learner_age_categories' => ['kids', 'teen', 'adult'],
            'external_platform' => 'zoom',
            'external_url' => 'https://zoom.example.test/j/123',
        ], $overrides);
    }

    private function seedCaviteAddress(): void
    {
        DB::table('cities')->updateOrInsert(
            ['code' => '402101000'],
            ['name' => 'Cavite City', 'region_code' => '400000000', 'province_code' => '402100000', 'is_city' => true, 'created_at' => now(), 'updated_at' => now()]
        );

        DB::table('barangays')->updateOrInsert(
            ['code' => '402101001'],
            ['name' => 'Barangay Test', 'city_code' => '402101000', 'created_at' => now(), 'updated_at' => now()]
        );
    }

    private function connectorPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Cavite Youth Health Network',
            'category' => 'ngo',
            'organization_type' => 'ngo_nonprofit_foundation',
            'organization_email' => null,
            'contact_number' => '09171234567',
            'city_code' => '402101000',
            'barangay_code' => '402101001',
            'address_line' => '123 Community Road',
            'description' => 'Community health education partner.',
            'website_url' => null,
            'verification_notes' => 'Accredited locally.',
            'confirm_accuracy' => '1',
            'reuse_representative_identity' => '0',
            'logo' => UploadedFile::fake()->create('logo.png', 100, 'image/png'),
            'evidence' => [
                'sec_nonstock_registration' => [
                    'files' => [UploadedFile::fake()->create('registration.pdf', 100, 'application/pdf')],
                ],
                'representative_identity' => [
                    'files' => [UploadedFile::fake()->create('identity.pdf', 100, 'application/pdf')],
                ],
                'representative_authority' => [
                    'files' => [UploadedFile::fake()->create('authority.pdf', 100, 'application/pdf')],
                    'issuer' => 'Board of Trustees',
                    'issue_date' => '2026-10-07',
                ],
            ],
        ], $overrides);
    }

    private function createVerifiedConnector(User $owner): Connector
    {
        $connector = Connector::create([
            ...$this->connectorPayload(['name' => 'Verified Connector']),
            'slug' => 'verified-connector-'.str()->random(6),
            'status' => 'verified',
            'verification_status' => Connector::VERIFICATION_LEGACY_VERIFIED,
            'created_by' => $owner->id,
            'primary_representative_user_id' => $owner->id,
        ]);

        $role = app(ConnectorRoleService::class)->createDefaultOwnerRole($connector);
        $connector->memberships()->create([
            'user_id' => $owner->id,
            'connector_role_id' => $role->id,
            'status' => 'active',
            'accepted_at' => now(),
        ]);

        return $connector->fresh(['roles.permissions', 'memberships.role']);
    }

    private function createCustomRole(Connector $connector, array $permissions = []): ConnectorRole
    {
        return app(ConnectorRoleService::class)->createRole($connector, [
            'name' => 'Coordinator '.str()->random(5),
            'permissions' => $permissions,
        ]);
    }

    private function createAdultConnectorMember(Connector $connector, array $permissions = []): User
    {
        $user = $this->createCompletedLearner([
            'birthdate' => now()->subYears(24)->toDateString(),
            'age' => 24,
            'account_type' => User::ACCOUNT_TYPE_LEARNER_ADULT,
        ]);
        $role = $this->createCustomRole($connector, $permissions);

        $connector->memberships()->create([
            'user_id' => $user->id,
            'connector_role_id' => $role->id,
            'status' => 'active',
            'accepted_at' => now(),
        ]);

        return $user->fresh();
    }

    private function createMinorLearner(int $age = 15): User
    {
        return $this->createCompletedLearner([
            'birthdate' => now()->subYears($age)->toDateString(),
            'age' => $age,
            'account_type' => $age <= 12 ? User::ACCOUNT_TYPE_LEARNER_CHILD : User::ACCOUNT_TYPE_LEARNER_TEEN,
        ]);
    }

    private function createCompletedLearner(array $attributes = []): User
    {
        $this->seedCaviteAddress();
        $user = User::factory()->create(array_merge(['role' => 'learner'], $attributes));
        $user->assignRole('learner');

        LearnerProfile::create([
            'user_id' => $user->id,
            'username' => 'learner_'.str()->random(6),
            'birthdate' => now()->subYears(21)->toDateString(),
            'city_code' => '402101000',
            'barangay_code' => '402101001',
            'barangay' => 'Barangay Test',
        ]);

        return $user;
    }

    private function createConnectorModulePaymentState(
        array $moduleOverrides = [],
        array $listingOverrides = [],
        array $purchaseOverrides = [],
        array $paymentOverrides = [],
    ): array {
        $manager = User::factory()->create();
        $connector = $this->createVerifiedConnector($manager);
        $module = Module::factory()->create(array_merge([
            'content_owner_type' => 'admin',
            'is_published' => true,
            'current_review_status' => 'approved',
        ], $moduleOverrides));
        $listing = ModuleMarketplaceListing::query()->create(array_merge([
            'module_id' => $module->id,
            'seller_type' => 'platform',
            'status' => ModuleMarketplaceListing::STATUS_PUBLISHED,
            'price_amount' => 2500,
            'currency' => 'PHP',
            'seat_capacity' => 10,
        ], $listingOverrides));
        $purchase = ConnectorModulePurchase::query()->create(array_merge([
            'connector_id' => $connector->id,
            'module_marketplace_listing_id' => $listing->id,
            'module_id' => $module->id,
            'purchased_by' => $manager->id,
            'status' => ConnectorModulePurchase::STATUS_PENDING,
            'gross_amount' => 2500,
            'currency' => 'PHP',
            'seat_capacity' => 10,
            'seller_type_snapshot' => 'platform',
            'module_title_snapshot' => $module->title,
            'terms_version' => ConnectorModulePurchase::TERMS_VERSION,
            'terms_accepted_at' => now(),
            'capacity_hold_expires_at' => now()->addMinutes(30),
        ], $purchaseOverrides));
        $payment = Payment::query()->create(array_merge([
            'user_id' => $manager->id,
            'subscription_id' => null,
            'amount' => 2500,
            'method' => 'gcash',
            'status' => PaymentStatus::Pending,
            'payment_details' => [
                'payment_scope' => 'connector_module_purchase',
                'connector_id' => $connector->id,
                'connector_module_purchase_id' => $purchase->id,
                'marketplace_listing_id' => $listing->id,
                'module_id' => $module->id,
                'paymongo_checkout_session_id' => 'cs_connector_test',
            ],
        ], $paymentOverrides));
        $purchase->update(['payment_id' => $payment->id]);

        return [$manager, $connector, $module, $listing, $purchase, $payment];
    }
}
