<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class MinorAccountHandoff extends Model
{
    public const PURPOSE_ACTIVATION = 'activation';

    public const PURPOSE_PASSWORD_RESET = 'password_reset';

    protected $fillable = [
        'child_user_id',
        'guardian_user_id',
        'parent_child_account_id',
        'purpose',
        'token_hash',
        'approved_at',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function setPurposeAttribute(mixed $value): void
    {
        if (! in_array($value, [self::PURPOSE_ACTIVATION, self::PURPOSE_PASSWORD_RESET], true)) {
            throw new InvalidArgumentException('Unsupported minor account handoff purpose.');
        }

        $this->attributes['purpose'] = $value;
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_user_id');
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_user_id');
    }

    public function relationship(): BelongsTo
    {
        return $this->belongsTo(ParentChildAccount::class, 'parent_child_account_id');
    }
}
