<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorViolationHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'module_id',
        'module_review_request_id',
        'reason_code',
        'guidance_note',
        'violation_sequence',
        'suggested_penalty_action',
        'confirmed_penalty_action',
        'confirmed_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'violation_sequence' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function reviewRequest(): BelongsTo
    {
        return $this->belongsTo(ModuleReviewRequest::class, 'module_review_request_id');
    }

    public function confirmedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_admin_id');
    }
}
