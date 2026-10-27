<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearnerIdentityVerification extends Model
{
    protected $fillable = [
        'user_id',
        'pathway',
        'document_type',
        'government_id_type',
        'government_id_type_other',
        'status',
        'submission_round',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'approved_at',
        'rejection_reason',
        'superseded_at',
    ];

    protected function casts(): array
    {
        return [
            'submission_round' => 'integer',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'superseded_at' => 'datetime',
        ];
    }

    public function learner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(LearnerIdentityEvidence::class, 'verification_id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(LearnerIdentityAudit::class, 'verification_id');
    }

    public function personaInquiries(): HasMany
    {
        return $this->hasMany(PersonaInquiry::class, 'learner_identity_verification_id');
    }

    public function usesPersonaSelfie(): bool
    {
        if ($this->pathway !== 'teen' || ! in_array($this->document_type, ['school_id', 'institution_id'], true)) {
            return false;
        }

        return $this->evidence_source === 'persona_selfie'
            || (! $this->evidence()->where('slot', 'selfie')->exists()
                && $this->personaInquiries()->where('user_id', $this->user_id)
                    ->where('target_submission_round', $this->submission_round)
                    ->whereNull('superseded_at')->where('flow_type', PersonaInquiry::FLOW_TEEN_SCHOOL)
                    ->whereNotNull('selfie_verification_id')->exists());
    }
}
