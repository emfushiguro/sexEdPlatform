<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\Auth\AdultRecoveryCodeService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class AdultRecoveryCodeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'database', 'session.connection' => null, 'app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->boolean('parent_identity_sandbox_manual_review')->default(false); $t->boolean('parent_identity_resubmission_allowed')->default(true); $t->string('name'); $t->date('birthdate'); $t->string('password'); $t->timestamp('minor_account_activated_at')->nullable(); $t->string('remember_token')->nullable(); $t->timestamps(); $t->softDeletes(); });
        Schema::create('sessions', function (Blueprint $t) { $t->string('id')->primary(); $t->unsignedBigInteger('user_id')->nullable(); });
        Schema::create('dependent_adult_recovery_codes', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('user_id'); $t->string('code_hash', 64)->unique(); $t->timestamp('used_at')->nullable(); $t->timestamps(); });
    }

    public function test_codes_are_single_use_replaceable_and_reset_terminates_sessions(): void
    {
        $user = User::withoutEvents(fn () => User::forceCreate(['name'=>'Adult', 'birthdate'=>'1990-01-01', 'password'=>Hash::make('Previous1!'), 'minor_account_activated_at'=>now()]));
        DB::table('sessions')->insert(['id'=>'old-session', 'user_id'=>$user->id]);
        $service = app(AdultRecoveryCodeService::class);
        $first = $service->generate($user);
        $this->assertCount(8, $first);
        $this->assertNotSame($first[0], DB::table('dependent_adult_recovery_codes')->first()->code_hash);
        $second = $service->generate($user);
        try { $service->reset($user, $first[0], 'Replacement1!'); $this->fail('Replaced code accepted'); } catch (InvalidArgumentException) {}
        $service->reset($user, $second[0], 'Replacement1!');
        $this->assertTrue(Hash::check('Replacement1!', $user->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id'=>'old-session']);
        $this->expectException(InvalidArgumentException::class);
        $service->reset($user, $second[0], 'Replacement2!');
    }
    public function test_recovery_rejects_minor_and_unsupported_session_storage_without_consuming_code(): void
    {
        $minor = User::withoutEvents(fn () => User::forceCreate(['name'=>'Minor', 'birthdate'=>now()->subYears(15), 'password'=>Hash::make('Previous1!'), 'minor_account_activated_at'=>now()]));
        $service = app(AdultRecoveryCodeService::class);
        $this->assertFalse($service->eligible($minor));
        $adult = User::withoutEvents(fn () => User::forceCreate(['name'=>'Adult', 'birthdate'=>'1990-01-01', 'password'=>Hash::make('Previous1!'), 'minor_account_activated_at'=>now()]));
        $code = $service->generate($adult)[0];
        config(['session.driver'=>'file']);
        try { $service->reset($adult, $code, 'Replacement1!'); $this->fail('Unsupported session driver accepted'); } catch (\RuntimeException) {}
        $this->assertTrue(Hash::check('Previous1!', $adult->fresh()->password));
        $this->assertSame(0, DB::table('dependent_adult_recovery_codes')->whereNotNull('used_at')->count());
    }
}
