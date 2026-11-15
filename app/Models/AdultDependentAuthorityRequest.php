<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdultDependentAuthorityRequest extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['authorized_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }

    public function relationship(): BelongsTo
    {
        return $this->belongsTo(ParentChildAccount::class, 'parent_child_account_id');
    }

    public static function identityHash(User $dependent): string
    {
        return hash('sha256', implode('|', [$dependent->id, trim((string) $dependent->name), $dependent->birthdate?->toDateString()]));
    }
}
