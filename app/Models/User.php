<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Carbon\Carbon;
use App\Notifications\CustomVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'middle_initial',
        'last_name',
        'suffix',
        'email',
        'birthdate',
        'age',
        'password',
        'role',
        'status',
        'verified',
        'is_parent_registration',
        'parent_verification_status',
        'parent_id_document_path',
        'parent_verification_rejection_reason',
        'parent_verification_reviewed_by',
        'parent_verification_reviewed_at',
        'parent_verification_approved_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'verified' => 'boolean',
            'birthdate' => 'date',
            'is_parent_registration' => 'boolean',
            'parent_verification_reviewed_at' => 'datetime',
            'parent_verification_approved_at' => 'datetime',
        ];
    }

    /**
     * Boot the model and auto-create gamification record.
     */
    protected static function booted(): void
    {
        static::created(function (User $user) {
            $user->gamification()->create([
                'level' => 1,
                'score' => 0,
                'streak_count' => 0,
            ]);
        });
    }

    /**
     * Send the email verification notification using custom template.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new CustomVerifyEmail);
    }

    // Relationships

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function learnerProfile()
    {
        return $this->hasOne(LearnerProfile::class);
    }

    public function subscription()
    {
        // Return active subscription first, then fall back to latest
        return $this->hasOne(Subscription::class)
            ->where(function ($query) {
                $query->where('status', 'active')
                    ->orWhere('status', 'trialing');
            })
            ->latest();
    }

    /**
     * Get the latest subscription regardless of status
     */
    public function latestSubscription()
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    /**
     * Get the active subscription specifically
     */
    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', now());
            });
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function instructorSaleLedgers()
    {
        return $this->hasMany(ModuleSaleLedger::class, 'instructor_id');
    }

    public function learnerSaleLedgers()
    {
        return $this->hasMany(ModuleSaleLedger::class, 'learner_id');
    }

    public function instructorOverridePolicies()
    {
        return $this->hasMany(CommissionPolicy::class, 'scope_id')
            ->where('scope_type', CommissionPolicy::SCOPE_INSTRUCTOR);
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function subscriptionPlan()
    {
        return $this->hasOneThrough(
            SubscriptionPlan::class, 
            Subscription::class, 
            'user_id', 
            'id', 
            'id', 
            'plan_id'
        )->where('subscriptions.status', 'active');
    }

    public function counselor()
    {
        return $this->hasOne(Counselor::class);
    }

    public function clinic()
    {
        return $this->hasOne(Clinic::class);
    }

    public function organization()
    {
        return $this->hasOne(Organization::class);
    }

    public function moduleEnrollments()
    {
        return $this->hasMany(ModuleEnrollment::class);
    }

    public function modulePurchases()
    {
        return $this->hasMany(ModulePurchase::class);
    }

    public function chatConversationsAsParticipantOne()
    {
        return $this->hasMany(Conversation::class, 'participant_one_id');
    }

    public function chatConversationsAsParticipantTwo()
    {
        return $this->hasMany(Conversation::class, 'participant_two_id');
    }

    public function chatMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function chatMessageRequestsCreated()
    {
        return $this->hasMany(MessageRequest::class, 'requester_id');
    }

    public function chatMessageRequestsAsInstructor()
    {
        return $this->hasMany(MessageRequest::class, 'instructor_id');
    }

    public function chatConversationReads()
    {
        return $this->hasMany(ConversationRead::class);
    }

    public function authoredModules()
    {
        return $this->hasMany(Module::class, 'created_by');
    }

    public function moderationProfile()
    {
        return $this->hasOne(InstructorModerationProfile::class);
    }

    public function violationHistories()
    {
        return $this->hasMany(InstructorViolationHistory::class);
    }

    public function moduleReviewsSubmitted()
    {
        return $this->hasMany(ModuleRevision::class, 'submitted_by');
    }

    public function moduleReviewsReviewed()
    {
        return $this->hasMany(ModuleRevision::class, 'reviewed_by');
    }

    public function userProgress()
    {
        return $this->hasMany(UserProgress::class);
    }

    // Alias for userProgress (for cleaner syntax)
    public function progress()
    {
        return $this->hasMany(UserProgress::class);
    }

    public function gamification()
    {
        return $this->hasOne(UserGamification::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function achievements()
    {
        return $this->belongsToMany(Achievement::class, 'rewards_logs')
            ->withPivot('earned_at')
            ->withTimestamps();
    }

    public function quizAttempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function seminarRegistrants()
    {
        return $this->hasMany(SeminarRegistrant::class);
    }

    public function consultations()
    {
        return $this->hasMany(Consultation::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    // Helper Methods

    public function isPremium(): bool
    {
        // Force refresh subscription to avoid cached status issues
        $this->load('subscription');
        return $this->subscription?->isPremium() ?? false;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isCounselor(): bool
    {
        return $this->role === 'counselor';
    }

    public function isClinic(): bool
    {
        return $this->role === 'clinic';
    }

    public function isOrganization(): bool
    {
        return $this->role === 'organization';
    }

    public function isInstructor(): bool
    {
        return $this->role === 'instructor';
    }

    /**
     * Get user's full name
     */
    public function getFullNameAttribute(): string
    {
        $name = trim("{$this->first_name}");
        
        if ($this->middle_initial) {
            $name .= " {$this->middle_initial}.";
        }
        
        $name .= " {$this->last_name}";
        
        if ($this->suffix) {
            $name .= " {$this->suffix}";
        }
        
        return $name;
    }

    /**
     * Calculate age from birthdate
     */
    public function calculateAge(): ?int
    {
        if (!$this->birthdate) {
            return null;
        }
        
        return Carbon::parse($this->birthdate)->age;
    }

    /**
     * Check if user is under 13 (requires parental consent)
     */
    public function requiresParentalConsent(): bool
    {
        return $this->calculateAge() < 13;
    }

    /**
     * Check if user is 18+ (can be a parent)
     */
    public function canBeParent(): bool
    {
        return $this->calculateAge() >= 18;
    }

    /**
     * Check if user is a parent with children
     */
    public function isParent(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Get parent-child relationships where user is parent
     */
    public function children()
    {
        return $this->belongsToMany(User::class, 'parent_child_accounts', 'parent_user_id', 'child_user_id')
            ->withPivot([
                'can_view_progress',
                'can_view_quiz_answers',
                'can_approve_content',
                'relationship_verified_at',
                'verification_status',
                'verification_document_path',
                'verification_rejection_reason',
                'verification_reviewed_by',
                'verification_reviewed_at',
                'verification_approved_at',
            ])
            ->withTimestamps();
    }

    /**
     * Get parent of this user (if they are a child)
     */
    public function parent()
    {
        return $this->belongsToMany(User::class, 'parent_child_accounts', 'child_user_id', 'parent_user_id')
            ->withPivot([
                'can_view_progress',
                'can_view_quiz_answers',
                'can_approve_content',
                'relationship_verified_at',
                'verification_status',
                'verification_document_path',
                'verification_rejection_reason',
                'verification_reviewed_by',
                'verification_reviewed_at',
                'verification_approved_at',
            ])
            ->withTimestamps()
            ->first();
    }

    public function parentChildLink()
    {
        return $this->hasOne(ParentChildAccount::class, 'child_user_id');
    }

    public function isParentRegistration(): bool
    {
        return (bool) $this->is_parent_registration;
    }

    public function isParentVerificationApproved(): bool
    {
        return $this->parent_verification_status === 'approved';
    }

    public function isParentVerificationPending(): bool
    {
        return ($this->parent_verification_status ?? 'pending') === 'pending';
    }

    public function isParentVerificationRejected(): bool
    {
        return $this->parent_verification_status === 'rejected';
    }

    /**
     * Check if learner profile is completed
     */
    public function hasCompletedProfile(): bool
    {
        if (!$this->isLearner()) {
            return true;
        }

        return $this->learnerProfile?->isCompleted() ?? false;
    }

    public function isLearner(): bool
    {
        return $this->role === 'learner';
    }

    public function instructorApplication()
    {
        return $this->hasOne(InstructorApplication::class)->latestOfMany();
    }

    public function instructorApplications()
    {
        return $this->hasMany(InstructorApplication::class);
    }

    public function instructorProfile()
    {
        return $this->hasOne(InstructorProfile::class);
    }

    public function roleTransitions()
    {
        return $this->hasMany(RoleTransition::class);
    }
}
