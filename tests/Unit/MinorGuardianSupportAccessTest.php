<?php

namespace Tests\Unit;

use App\Http\Requests\DependentSupport\UpdateGuardianSupportAccessRequest;
use App\Models\ParentChildAccount;
use App\Models\DependentSupportProfile;
use App\Models\User;
use App\Policies\DependentSupportProfilePolicy;
use App\Services\DependentSupportInformationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MinorGuardianSupportAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'persona.local_manual_review' => false]);
        DB::purge('sqlite');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Schema::create('users', function (Blueprint $table): void {
            $table->id(); $table->date('birthdate')->nullable(); $table->string('status')->default('active');
            $table->string('parent_verification_status')->nullable(); $table->boolean('parent_identity_sandbox_manual_review')->default(false);
            $table->softDeletes();
        });
        Schema::create('parent_child_accounts', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('parent_user_id'); $table->unsignedBigInteger('child_user_id');
            $table->string('relationship_status')->default('active'); $table->string('relationship_verified_status')->default('verified');
            $table->timestamp('relationship_verified_at')->nullable(); $table->timestamp('adult_permissions_confirmed_at')->nullable();
            $table->string('verification_document_path')->nullable(); $table->string('verification_status')->nullable();
            $table->boolean('can_manage_support_information')->default(false); $table->boolean('can_view_progress')->default(false);
            $table->softDeletes();
        });
        DB::table('users')->insert([
            ['id' => 1, 'birthdate' => '1980-01-01', 'status' => 'active', 'parent_verification_status' => 'approved'],
            ['id' => 2, 'birthdate' => now()->subYears(15)->toDateString(), 'status' => 'active', 'parent_verification_status' => null],
        ]);
        DB::table('parent_child_accounts')->insert(['parent_user_id' => 1, 'child_user_id' => 2, 'relationship_verified_at' => now()]);
    }

    public function test_verified_eligible_guardian_automatically_manages_minor_support_but_not_other_permissions(): void
    {
        $this->assertTrue((new DependentSupportProfilePolicy())->create(User::findOrFail(1), User::findOrFail(2)));
        $profile = (new DependentSupportProfile())->setRelation('dependent', User::findOrFail(2));
        foreach (['view', 'update', 'delete'] as $ability) {
            $this->assertTrue((new DependentSupportProfilePolicy())->{$ability}(User::findOrFail(1), $profile));
        }
        $this->assertFalse(ParentChildAccount::accessEligible()->withPermission('can_view_progress')->exists());
        DB::table('parent_child_accounts')->update(['relationship_status' => 'revoked']);
        $this->assertFalse((new DependentSupportProfilePolicy())->create(User::findOrFail(1), User::findOrFail(2)));
    }

    public function test_unverified_or_suspended_guardian_cannot_manage_minor_support(): void
    {
        foreach ([['parent_verification_status' => 'pending'], ['parent_verification_status' => 'approved', 'status' => 'suspended']] as $state) {
            DB::table('users')->where('id', 1)->update($state);
            $this->assertFalse((new DependentSupportProfilePolicy())->create(User::findOrFail(1), User::findOrFail(2)));
        }
    }

    public function test_eighteenth_birthday_requires_adult_confirmation_and_explicit_permission(): void
    {
        DB::table('users')->where('id', 2)->update(['birthdate' => now()->subYears(18)->toDateString()]);
        foreach ([['can_manage_support_information' => false, 'adult_permissions_confirmed_at' => null], ['can_manage_support_information' => true, 'adult_permissions_confirmed_at' => null], ['can_manage_support_information' => false, 'adult_permissions_confirmed_at' => now()]] as $state) {
            DB::table('parent_child_accounts')->update($state);
            $this->assertFalse((new DependentSupportProfilePolicy())->create(User::findOrFail(1), User::findOrFail(2)));
        }
        DB::table('parent_child_accounts')->update(['can_manage_support_information' => true]);
        $this->assertTrue((new DependentSupportProfilePolicy())->create(User::findOrFail(1), User::findOrFail(2)));
    }

    public function test_minor_cannot_toggle_automatic_access_through_request_or_service(): void
    {
        $minor = User::findOrFail(2);
        $request = new UpdateGuardianSupportAccessRequest();
        $request->setUserResolver(fn () => $minor);
        $this->assertFalse($request->authorize());
        $this->expectException(AuthorizationException::class);
        app(DependentSupportInformationService::class)->setGuardianAccess(ParentChildAccount::firstOrFail(), $minor, false);
    }
}
