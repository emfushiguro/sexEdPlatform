<?php

namespace Tests\Unit\Models;

use App\Models\Module;
use App\Models\ModuleReviewRequest;
use App\Models\ModuleRevision;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PHPUnit\Framework\Attributes\Test;
use Tests\UnitTestCase;

class ModuleGovernanceRelationshipsTest extends UnitTestCase
{
    #[Test]
    public function module_exposes_revision_and_review_relationships(): void
    {
        $module = new Module();

        $this->assertInstanceOf(HasMany::class, $module->revisions());
        $this->assertSame(ModuleRevision::class, $module->revisions()->getRelated()::class);

        $this->assertInstanceOf(HasMany::class, $module->reviewRequests());
        $this->assertSame(ModuleReviewRequest::class, $module->reviewRequests()->getRelated()::class);

        $this->assertInstanceOf(BelongsTo::class, $module->publishedRevision());
        $this->assertSame(ModuleRevision::class, $module->publishedRevision()->getRelated()::class);

        $this->assertInstanceOf(BelongsTo::class, $module->publisher());
        $this->assertSame(User::class, $module->publisher()->getRelated()::class);
    }

    #[Test]
    public function user_exposes_module_revision_submission_and_review_relationships(): void
    {
        $user = new User();

        $this->assertInstanceOf(HasMany::class, $user->moduleReviewsSubmitted());
        $this->assertSame(ModuleRevision::class, $user->moduleReviewsSubmitted()->getRelated()::class);

        $this->assertInstanceOf(HasMany::class, $user->moduleReviewsReviewed());
        $this->assertSame(ModuleRevision::class, $user->moduleReviewsReviewed()->getRelated()::class);
    }
}
