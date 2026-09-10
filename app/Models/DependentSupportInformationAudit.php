<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DependentSupportInformationAudit extends Model
{
    public const ACTION_CREATED = 'created';

    public const ACTION_UPDATED = 'updated';

    public const ACTION_REMOVED = 'removed';

    public const ACTION_PERMISSION_GRANTED = 'permission_granted';

    public const ACTION_PERMISSION_REVOKED = 'permission_revoked';

    public $timestamps = false;

    protected $fillable = [
        'dependent_user_id',
        'actor_user_id',
        'parent_child_account_id',
        'action',
        'changed_fields',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'changed_fields' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function dependent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dependent_user_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function relationship(): BelongsTo
    {
        return $this->belongsTo(ParentChildAccount::class, 'parent_child_account_id');
    }
}
