<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'verified',
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
        ];
    }

    // Relationships

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
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

    public function userProgress()
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
        return $this->subscription?->plan === 'premium' && $this->subscription?->status === 'active';
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

    public function isLearner(): bool
    {
        return $this->role === 'learner';
    }
}
