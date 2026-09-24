<?php

namespace Tests\Feature\Identity;

use App\Http\Requests\Auth\SubmitLearnerIdentityRequest;
use App\Models\User;
use App\Services\Identity\LearnerIdentityRequirement;
use App\Services\Identity\LearnerIdentitySubmission;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LearnerIdentitySubmissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-24 10:00:00');
        Storage::fake('local');
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_adult_submission_stores_private_evidence_and_audit(): void
    {
        [$adult, $case] = $this->case('2000-01-01');
        $submitted = $this->submit($adult, $case, 'government_id', 'philhealth');

        $this->assertSame('pending', $submitted->status);
        $this->assertSame(1, $submitted->submission_round);
        $this->assertEqualsCanonicalizing(['identity_front', 'selfie'], $submitted->evidence->pluck('slot')->all());
        foreach ($submitted->evidence as $evidence) {
            $this->assertStringStartsWith("learner-verifications/{$adult->id}/adult/", $evidence->storage_path);
            Storage::disk('local')->assertExists($evidence->storage_path);
            $this->assertSame('image/png', $evidence->mime_type);
            $this->assertGreaterThan(0, $evidence->byte_size);
            $this->assertGreaterThanOrEqual(320, $evidence->width);
        }
        $this->assertSame(['created', 'submitted'], $case->audits()->pluck('action')->all());
        $this->assertSame($adult->id, $case->audits()->where('action', 'submitted')->firstOrFail()->actor_id);
        $this->assertSame([], Storage::disk('public')->allFiles('learner-verifications'));
    }

    public function test_document_choices_and_back_requirements(): void
    {
        foreach (['school_id', 'institution_id', 'government_id'] as $type) {
            [$teen, $case] = $this->case('2010-01-01');
            $this->submit($teen, $case, $type, $type === 'government_id' ? 'national_id' : null,
                $type === 'government_id' ? ['identity_back' => $this->image('back.jpg')] : []);
            $this->assertSame($type, $case->fresh()->document_type);
        }
        [$adult, $case] = $this->case('2000-01-01');
        $this->assertFalse($this->valid($adult, $case, ['document_type' => 'school_id', 'confirm_submission' => '1'], $this->files()));
        $this->assertTrue($this->valid($adult, $case, $this->data('government_id', 'other', 'Government-issued card'), $this->files(['identity_back' => $this->image('back.jpg')])));
        $this->assertFalse($this->valid($adult, $case, $this->data('government_id', 'other'), $this->files(['identity_back' => $this->image('back.jpg')])));
        $this->assertFalse($this->valid($adult, $case, $this->data('government_id', 'national_id'), $this->files()));
        $this->assertFalse($this->valid($adult, $case, $this->data('government_id', 'philhealth'), ['identity_front' => $this->image('front.jpg')]));
    }

    public function test_image_validation_rejects_invalid_files(): void
    {
        [$adult, $case] = $this->case('2000-01-01');
        foreach ([
            UploadedFile::fake()->createWithContent('bad.jpg', 'not an image'),
            UploadedFile::fake()->create('front.pdf', 100, 'application/pdf'),
            $this->image('big.png', 800, 800, 5121),
            $this->image('small.png', 319, 800),
            $this->image('wide.png', 6001, 800),
        ] as $file) {
            $this->assertFalse($this->valid($adult, $case, $this->data(), $this->files(['identity_front' => $file])));
        }
    }

    public function test_denies_foreign_superseded_pending_approved_and_age_mismatched_cases(): void
    {
        [$adult, $case] = $this->case('2000-01-01');
        [$other] = $this->case('2000-01-01');
        foreach ([[$other, $case], [$adult, tap($case->replicate(), fn ($c) => $c->fill(['pathway' => 'teen']))]] as [$actor, $target]) {
            try {
                app(LearnerIdentitySubmission::class)->submit($actor, $target, $this->data(), $this->files());
                $this->fail('Invalid case accepted.');
            } catch (\DomainException $e) {
                $this->assertNull($case->fresh()->status);
            }
        }
        $case->update(['superseded_at' => now()]);
        $this->expectException(\DomainException::class);
        app(LearnerIdentitySubmission::class)->submit($adult, $case, $this->data(), $this->files());
    }

    public function test_pending_and_approved_cases_cannot_be_replaced(): void
    {
        foreach (['pending', 'approved'] as $status) {
            [$adult, $case] = $this->case('2000-01-01');
            $case->update(['status' => $status]);
            try {
                $this->submit($adult, $case);
                $this->fail("{$status} case accepted.");
            } catch (\DomainException $e) {
                $this->assertSame($status, $case->fresh()->status);
            }
        }
    }

    public function test_rejection_allows_selective_replacement_and_deletes_old_bytes_after_commit(): void
    {
        [$adult, $case] = $this->case('2000-01-01');
        $first = $this->submit($adult, $case);
        $old = $first->evidence->pluck('storage_path', 'slot');
        $case->update(['status' => 'rejected', 'rejection_reason' => 'Blurry']);
        $second = app(LearnerIdentitySubmission::class)->submit($adult, $case, $this->data(), ['selfie' => $this->image('new-selfie.jpg')]);
        $paths = $second->evidence->pluck('storage_path', 'slot');
        $this->assertSame($old['identity_front'], $paths['identity_front']);
        $this->assertNotSame($old['selfie'], $paths['selfie']);
        Storage::disk('local')->assertMissing($old['selfie']);
        Storage::disk('local')->assertExists($old['identity_front']);
        $this->assertSame(2, $second->submission_round);
        $this->assertSame(['created', 'submitted', 'resubmitted'], $case->audits()->pluck('action')->all());
        $this->assertNull($second->rejection_reason);
    }

    public function test_document_change_requires_new_front_and_clears_obsolete_back(): void
    {
        [$teen, $case] = $this->case('2010-01-01');
        $first = $this->submit($teen, $case, 'government_id', 'national_id', ['identity_back' => $this->image('back.jpg')]);
        $old = $first->evidence->pluck('storage_path', 'slot');
        $case->update(['status' => 'rejected']);
        $this->assertFalse($this->valid($teen, $case, $this->data('school_id'), ['selfie' => $this->image('selfie.jpg')]));
        $second = app(LearnerIdentitySubmission::class)->submit($teen, $case, $this->data('school_id'), ['identity_front' => $this->image('new-front.jpg')]);
        $this->assertEqualsCanonicalizing(['identity_front', 'selfie'], $second->evidence->pluck('slot')->all());
        Storage::disk('local')->assertMissing([$old['identity_front'], $old['identity_back']]);
        Storage::disk('local')->assertExists($old['selfie']);
    }

    public function test_government_subtype_change_requires_new_front_and_back(): void
    {
        [$adult, $case] = $this->case('2000-01-01');
        $first = $this->submit($adult, $case, 'government_id', 'philhealth');
        $oldFront = $first->evidence->firstWhere('slot', 'identity_front')->storage_path;
        $case->update(['status' => 'rejected']);

        $passport = $this->data('government_id', 'passport');
        $this->assertFalse($this->valid($adult, $case, $passport, ['identity_back' => $this->image('passport-back.png')]));
        $this->assertFalse($this->valid($adult, $case, $passport, ['identity_front' => $this->image('passport-front.png')]));
        try {
            app(LearnerIdentitySubmission::class)->submit($adult, $case, $passport, ['identity_back' => $this->image('passport-back.png')]);
            $this->fail('Government subtype changed without a new front.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('identity_front', $e->errors());
        }
        $this->assertSame('philhealth', $case->fresh()->government_id_type);
        $this->assertSame($oldFront, $case->fresh()->evidence->firstWhere('slot', 'identity_front')->storage_path);

        $second = app(LearnerIdentitySubmission::class)->submit($adult, $case, $passport, [
            'identity_front' => $this->image('passport-front.png'),
            'identity_back' => $this->image('passport-back.png'),
        ]);
        $this->assertSame('passport', $second->government_id_type);
        $this->assertNotSame($oldFront, $second->evidence->firstWhere('slot', 'identity_front')->storage_path);
    }

    public function test_outer_transaction_rollback_preserves_replaced_evidence_bytes(): void
    {
        [$adult, $case] = $this->case('2000-01-01');
        $first = $this->submit($adult, $case);
        $old = $first->evidence->pluck('storage_path', 'slot');
        $case->update(['status' => 'rejected']);

        try {
            DB::transaction(function () use ($adult, $case): void {
                app(LearnerIdentitySubmission::class)->submit($adult, $case, $this->data(), [
                    'identity_front' => $this->image('replacement-front.png'),
                ]);
                throw new \RuntimeException('Outer caller rolled back.');
            });
        } catch (\RuntimeException $e) {
            $this->assertSame('Outer caller rolled back.', $e->getMessage());
        }

        $this->assertSame('rejected', $case->fresh()->status);
        $this->assertSame($old['identity_front'], $case->fresh()->evidence->firstWhere('slot', 'identity_front')->storage_path);
        Storage::disk('local')->assertExists($old['identity_front']);
        Storage::disk('local')->assertExists($old['selfie']);
    }

    public function test_failed_storage_cleans_new_files_and_preserves_rejected_evidence(): void
    {
        [$adult, $case] = $this->case('2000-01-01');
        $first = $this->submit($adult, $case);
        $old = $first->evidence->pluck('storage_path', 'slot');
        $case->update(['status' => 'rejected', 'rejection_reason' => 'Blurry']);

        $valid = $this->image('failing.png');
        $failing = new class($valid->getRealPath()) extends UploadedFile {
            public function __construct(string $path)
            {
                parent::__construct($path, 'failing.png', 'image/png', null, true);
            }

            public function store($path = '', $options = [])
            {
                return false;
            }
        };

        try {
            app(LearnerIdentitySubmission::class)->submit($adult, $case, $this->data(), [
                'identity_front' => $this->image('replacement.png'), 'selfie' => $failing,
            ]);
            $this->fail('Failed storage was accepted.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Identity evidence could not be stored.', $e->getMessage());
        }

        $this->assertSame('rejected', $case->fresh()->status);
        $this->assertSame('Blurry', $case->fresh()->rejection_reason);
        $this->assertSame(1, $case->fresh()->submission_round);
        $this->assertEqualsCanonicalizing($old->all(), $case->fresh()->evidence->pluck('storage_path')->all());
        $this->assertEqualsCanonicalizing($old->all(), Storage::disk('local')->allFiles('learner-verifications'));
        $this->assertSame(['created', 'submitted'], $case->audits()->pluck('action')->all());
    }

    public function test_request_authorizes_only_verified_current_owner_with_open_case(): void
    {
        [$adult, $case] = $this->case('2000-01-01');
        $request = SubmitLearnerIdentityRequest::create('/', 'POST', $this->data());
        $request->setUserResolver(fn () => $adult);
        $this->assertTrue($request->authorize());
        $case->update(['status' => 'pending']);
        $this->assertFalse($request->authorize());
        $case->update(['status' => 'rejected']);
        $this->assertTrue($request->authorize());
        $adult->forceFill(['email_verified_at' => null])->save();
        $this->assertFalse($request->authorize());
        $adult->forceFill(['email_verified_at' => now(), 'birthdate' => '2010-01-01'])->save();
        $this->assertFalse($request->authorize());
    }

    private function case(string $birthdate): array
    {
        $user = User::factory()->create(['role' => 'learner', 'birthdate' => $birthdate, 'email_verified_at' => now()]);
        $user->assignRole('learner');

        return [$user, app(LearnerIdentityRequirement::class)->createForNewLearner($user)];
    }

    private function image(string $name, int $width = 800, int $height = 600, ?int $kilobytes = null): UploadedFile
    {
        $chunk = static function (string $type, string $data): string {
            return pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
        };
        $raw = str_repeat("\x00".str_repeat("\x00\x00\x00", $width), $height);
        $png = "\x89PNG\r\n\x1a\n"
            .$chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0))
            .$chunk('IDAT', gzcompress($raw))
            .$chunk('IEND', '');
        if ($kilobytes !== null) {
            $png = str_pad($png, $kilobytes * 1024, "\x00");
        }

        return UploadedFile::fake()->createWithContent($name, $png);
    }

    private function files(array $overrides = []): array
    {
        return array_merge(['identity_front' => $this->image('front.jpg'), 'selfie' => $this->image('selfie.jpg')], $overrides);
    }

    private function data(string $type = 'government_id', ?string $subtype = 'philhealth', ?string $other = null): array
    {
        return array_filter(['document_type' => $type, 'government_id_type' => $type === 'government_id' ? $subtype : null,
            'government_id_type_other' => $other, 'confirm_submission' => '1'], fn ($v) => $v !== null);
    }

    private function submit(User $user, $case, string $type = 'government_id', ?string $subtype = 'philhealth', array $extraFiles = [])
    {
        return app(LearnerIdentitySubmission::class)->submit($user, $case, $this->data($type, $subtype), $this->files($extraFiles));
    }

    private function valid(User $user, $case, array $data, array $files): bool
    {
        $request = SubmitLearnerIdentityRequest::create('/', 'POST', $data, [], $files);
        $request->setUserResolver(fn () => $user);
        $this->assertTrue($request->authorize());

        return Validator::make(array_merge($data, $files), $request->rules())->passes();
    }
}
