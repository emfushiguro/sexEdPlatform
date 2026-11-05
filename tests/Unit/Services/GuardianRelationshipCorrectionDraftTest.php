<?php

namespace Tests\Unit\Services;

use App\Models\GuardianRelationshipVerificationDocument;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Services\GuardianRelationshipCorrectionDraft;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use Mockery;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class GuardianRelationshipCorrectionDraftTest extends TestCase
{
    private string $directory;

    private Container $container;

    private User $guardian;

    private ParentChildAccount $relationship;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/relationship-draft-'.bin2hex(random_bytes(8));
        $this->container = new Container;
        Container::setInstance($this->container);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->container);
        DB::swap(Mockery::mock(DatabaseManager::class));
        $this->container->instance('config', new Repository([
            'filesystems' => ['default' => 'local', 'disks' => ['local' => ['driver' => 'local', 'root' => $this->directory.'/private', 'throw' => true]]],
            'cache' => ['stores' => ['file' => ['driver' => 'file', 'path' => $this->directory.'/locks']]],
            'session' => ['lifetime' => 120],
            'guardian_relationships' => require dirname(__DIR__, 3).'/config/guardian_relationships.php',
        ]));
        $this->container->instance('files', new Filesystem);
        $this->container->instance('filesystem', new FilesystemManager($this->container));
        $this->container->instance('cache', new CacheManager($this->container));
        $this->container->instance('validator', new Factory(new Translator(new ArrayLoader, 'en'), $this->container));
        $this->container->instance('session', new Store('test', new ArraySessionHandler(120), str_repeat('a', 40)));
        $this->guardian = Mockery::mock(User::class)->makePartial();
        $this->guardian->forceFill(['id' => 7, 'status' => User::STATUS_ACTIVE]);
        $this->guardian->shouldReceive('isParentVerificationApproved')->andReturn(true);
        $this->relationship = Mockery::mock(ParentChildAccount::class)->makePartial();
        $this->relationship->forceFill([
            'id' => 11, 'parent_user_id' => 7, 'relationship_type' => 'biological_mother',
            'relationship_status' => 'pending', 'relationship_verified_status' => 'resubmission_required',
            'current_evidence_round' => 1,
            'relationship_correction_request' => ['round' => 1, 'document_ids' => [23], 'document_types' => ['agency_document']],
        ]);
        $documents = new Collection([(new GuardianRelationshipVerificationDocument)->forceFill([
            'id' => 23, 'document_type' => 'other_supporting_document', 'document_side' => 'not_applicable', 'pairing_key' => null,
        ])]);
        $query = Mockery::mock(HasMany::class);
        $query->shouldReceive('where', 'whereNull')->andReturnSelf();
        $query->shouldReceive('get')->andReturn($documents);
        $this->relationship->shouldReceive('verificationDocuments')->andReturn($query);
        $this->relationship->shouldReceive('fresh')->andReturn($this->relationship);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);
        (new Filesystem)->deleteDirectory($this->directory);
        parent::tearDown();
    }

    public function test_individual_saves_survive_reloading_and_do_not_overwrite_other_slots(): void
    {
        $first = new GuardianRelationshipCorrectionDraft;
        $first->save($this->guardian, $this->relationship, 1, 'document:23', $this->file('support.pdf'));
        $second = new GuardianRelationshipCorrectionDraft;
        $second->save($this->guardian, $this->relationship, 1, 'type:agency_document', $this->file('agency.pdf'));
        $saved = (new GuardianRelationshipCorrectionDraft)->current($this->guardian, $this->relationship);
        $this->assertSame(['document:23', 'type:agency_document'], array_keys($saved));
        $this->assertSame('support.pdf', $saved['document:23']['name']);
        $this->container['session']->setId(str_repeat('b', 40));
        $this->assertSame([], $second->current($this->guardian, $this->relationship));
    }

    public function test_stale_round_and_arbitrary_slots_preserve_existing_saves(): void
    {
        $drafts = new GuardianRelationshipCorrectionDraft;
        $drafts->save($this->guardian, $this->relationship, 1, 'document:23', $this->file());
        foreach ([[0, 'document:23'], [1, 'document:99'], [1, 'type:court_order']] as [$round, $slot]) {
            try {
                $drafts->save($this->guardian, $this->relationship, $round, $slot, $this->file());
                $this->fail('Invalid slot or round must fail.');
            } catch (ValidationException) {
                $this->assertCount(1, $drafts->current($this->guardian, $this->relationship));
            }
        }
    }

    public function test_another_guardian_cannot_read_or_save_the_draft(): void
    {
        $other = (new User)->forceFill(['id' => 8, 'status' => User::STATUS_ACTIVE]);
        $this->expectException(AuthorizationException::class);
        (new GuardianRelationshipCorrectionDraft)->save($other, $this->relationship, 1, 'document:23', $this->file());
    }

    public function test_saved_files_populate_final_request_without_native_file_inputs(): void
    {
        $drafts = $this->completeDraft();
        $request = Request::create('/', 'POST', ['current_evidence_round' => 1]);
        $drafts->populate($request, $this->guardian, $this->relationship);
        $this->assertInstanceOf(UploadedFile::class, $request->file('documents.0.file'));
        $this->assertSame(23, $request->input('documents.0.replacement_document_id'));
        $this->assertSame('agency_document', $request->input('documents.1.document_type'));
    }

    public function test_missing_saves_and_direct_uploads_cannot_skip_the_save_step(): void
    {
        $drafts = new GuardianRelationshipCorrectionDraft;
        $drafts->save($this->guardian, $this->relationship, 1, 'document:23', $this->file());
        foreach ([[], ['documents' => [['file' => $this->file()]]]] as $files) {
            try {
                $drafts->populate(Request::create('/', 'POST', ['current_evidence_round' => 1], [], $files), $this->guardian, $this->relationship);
                $this->fail('Every correction must be saved first.');
            } catch (ValidationException) {
                $this->assertCount(1, $drafts->current($this->guardian, $this->relationship));
            }
        }
    }

    public function test_submission_failure_preserves_drafts_and_success_clears_only_this_round(): void
    {
        $drafts = $this->completeDraft();
        $request = Request::create('/', 'POST', ['current_evidence_round' => 1]);
        $drafts->populate($request, $this->guardian, $this->relationship);
        try {
            $drafts->submit($request, $this->guardian, $this->relationship, fn () => throw new RuntimeException('Submission failed.'));
            $this->fail('Expected submission failure.');
        } catch (RuntimeException) {
            $this->assertCount(2, $drafts->current($this->guardian, $this->relationship));
        }
        DB::shouldReceive('transactionLevel')->once()->andReturn(0);
        $this->assertSame('submitted', $drafts->submit($request, $this->guardian, $this->relationship, fn () => 'submitted'));
        $this->assertSame([], $drafts->current($this->guardian, $this->relationship));
    }

    public function test_a_save_during_final_validation_forces_a_retry_and_preserves_the_new_file(): void
    {
        $drafts = $this->completeDraft();
        $request = Request::create('/', 'POST', ['current_evidence_round' => 1]);
        $drafts->populate($request, $this->guardian, $this->relationship);
        $drafts->save($this->guardian, $this->relationship, 1, 'document:23', $this->file('newer.pdf'));
        try {
            $drafts->submit($request, $this->guardian, $this->relationship, fn () => $this->fail('Stale snapshot must not submit.'));
            $this->fail('Expected revision conflict.');
        } catch (ValidationException) {
            $this->assertSame('newer.pdf', $drafts->current($this->guardian, $this->relationship)['document:23']['name']);
        }
    }

    public function test_tampered_file_is_not_saved_and_closed_claims_cannot_save(): void
    {
        $drafts = $this->completeDraft();
        $saved = $drafts->current($this->guardian, $this->relationship);
        Storage::disk('local')->put($saved['document:23']['path'], 'tampered');
        $this->assertArrayNotHasKey('document:23', $drafts->current($this->guardian, $this->relationship));
        $this->relationship->relationship_verified_status = 'rejected';
        $this->expectException(ValidationException::class);
        $drafts->save($this->guardian, $this->relationship, 1, 'document:23', $this->file());
    }

    public function test_saving_again_removes_expired_bytes_and_preserves_other_valid_slots(): void
    {
        $drafts = $this->completeDraft();
        $saved = $drafts->current($this->guardian, $this->relationship);
        $disk = Storage::disk('local');
        $manifest = dirname($saved['document:23']['path']).'/manifest.json';
        $expired = $saved;
        $expired['document:23']['expires_at'] = time() - 1;
        $disk->put($manifest, json_encode($expired));

        $drafts->save($this->guardian, $this->relationship, 1, 'document:23', $this->file('new.pdf'));

        $disk->assertMissing($saved['document:23']['path']);
        $disk->assertExists($saved['type:agency_document']['path']);
        $current = $drafts->current($this->guardian, $this->relationship);
        $this->assertSame('new.pdf', $current['document:23']['name']);
        $this->assertSame($saved['type:agency_document'], $current['type:agency_document']);
    }

    public function test_scheduled_pruning_removes_abandoned_session_files_and_orphans_but_keeps_valid_drafts(): void
    {
        $drafts = $this->completeDraft();
        $saved = $drafts->current($this->guardian, $this->relationship);
        $disk = Storage::disk('local');
        $directory = dirname($saved['document:23']['path']);
        $expired = $saved;
        foreach ($expired as &$item) {
            $item['expires_at'] = time() - 1;
        }
        unset($item);
        $disk->put($directory.'/manifest.json', json_encode($expired));
        $orphan = 'guardian-relationship-correction-drafts/'.str_repeat('f', 64).'/'.str_repeat('a', 32);
        $disk->put($orphan, 'synthetic abandoned file');
        touch($disk->path($orphan), time() - 120 * 60 - 1);

        $this->container['session']->setId(str_repeat('b', 40));
        $drafts->save($this->guardian, $this->relationship, 1, 'document:23', $this->file('active.pdf'));
        $active = $drafts->current($this->guardian, $this->relationship);
        $drafts->pruneExpired();

        foreach ($saved as $item) {
            $disk->assertMissing($item['path']);
        }
        $disk->assertMissing($directory.'/manifest.json');
        $disk->assertMissing($orphan);
        $disk->assertExists($active['document:23']['path']);
        $this->assertSame('active.pdf', $drafts->current($this->guardian, $this->relationship)['document:23']['name']);
    }

    public function test_disguised_plaintext_cannot_replace_a_saved_pdf(): void
    {
        $drafts = $this->completeDraft();
        $saved = $drafts->current($this->guardian, $this->relationship);
        try {
            $drafts->save($this->guardian, $this->relationship, 1, 'document:23', UploadedFile::fake()->createWithContent('bad.pdf', 'This is not a PDF.'));
            $this->fail('File contents must match an accepted MIME type.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('file', $exception->errors());
            $this->assertSame($saved, $drafts->current($this->guardian, $this->relationship));
        }
    }

    public function test_immediate_after_commit_callback_runs_after_the_draft_lock_is_released(): void
    {
        $drafts = $this->completeDraft();
        $request = Request::create('/', 'POST', ['current_evidence_round' => 1]);
        $drafts->populate($request, $this->guardian, $this->relationship);
        $saved = $drafts->current($this->guardian, $this->relationship);
        $directory = dirname($saved['document:23']['path']);
        DB::shouldReceive('transactionLevel')->once()->andReturn(1);
        DB::shouldReceive('afterCommit')->once()->andReturnUsing(function ($cleanup) use ($directory): void {
            $lock = fopen(Storage::disk('local')->path($directory.'/.lock'), 'c');
            try {
                $this->assertTrue(flock($lock, LOCK_EX | LOCK_NB), 'Cleanup registration must happen after releasing the draft lock.');
            } finally {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
            $cleanup();
        });

        $this->assertSame('submitted', $drafts->submit($request, $this->guardian, $this->relationship, fn () => 'submitted'));
        $this->assertSame([], $drafts->current($this->guardian, $this->relationship));
    }

    private function completeDraft(): GuardianRelationshipCorrectionDraft
    {
        $drafts = new GuardianRelationshipCorrectionDraft;
        $drafts->save($this->guardian, $this->relationship, 1, 'document:23', $this->file('support.pdf'));
        $drafts->save($this->guardian, $this->relationship, 1, 'type:agency_document', $this->file('agency.pdf'));

        return $drafts;
    }

    private function file(string $name = 'evidence.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n".bin2hex(random_bytes(8))."\n%%EOF");
    }
}
