<?php

namespace Tests\Unit\Models;

use App\Enums\ModuleReviewRejectionReason;
use App\Models\InstructorModerationProfile;
use App\Models\InstructorViolationHistory;
use App\Models\ModuleReviewRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use PHPUnit\Framework\Attributes\Test;
use Tests\UnitTestCase;

class InstructorModerationRelationshipsTest extends UnitTestCase
{
    #[Test]
    public function user_exposes_moderation_profile_and_violation_relationships(): void
    {
        $user = new User();

        $this->assertInstanceOf(HasOne::class, $user->moderationProfile());
        $this->assertSame(InstructorModerationProfile::class, $user->moderationProfile()->getRelated()::class);

        $this->assertInstanceOf(HasMany::class, $user->violationHistories());
        $this->assertSame(InstructorViolationHistory::class, $user->violationHistories()->getRelated()::class);
    }

    #[Test]
    public function review_request_exposes_violation_records_relationship(): void
    {
        $reviewRequest = new ModuleReviewRequest();

        $this->assertInstanceOf(HasMany::class, $reviewRequest->violationRecords());
        $this->assertSame(InstructorViolationHistory::class, $reviewRequest->violationRecords()->getRelated()::class);
    }

    #[Test]
    public function module_review_rejection_reason_enum_exposes_required_reasons(): void
    {
        $this->assertSame([
            'inaccurate_educational_information',
            'inappropriate_content',
            'low_quality_lessons',
            'missing_content',
            'quiz_errors',
            'poor_module_structure',
            'other',
        ], ModuleReviewRejectionReason::values());
    }
}
