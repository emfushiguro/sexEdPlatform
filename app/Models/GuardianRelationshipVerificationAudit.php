<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuardianRelationshipVerificationAudit extends Model
{
    protected $fillable = [
        'parent_child_account_id',
        'actor_user_id',
        'action',
        'previous_status',
        'new_status',
        'submission_round',
        'reason_code',
        'notes',
    ];

    protected $casts = [
        'submission_round' => 'integer',
    ];

    public function relationship(): BelongsTo
    {
        return $this->belongsTo(ParentChildAccount::class, 'parent_child_account_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
