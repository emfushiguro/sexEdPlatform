<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleTransition extends Model
{
    protected $fillable = [
        'user_id',
        'from_role',
        'to_role',
        'approved_by',
        'reason',
        'custom_notes',
        'preserved_data',
        'transitioned_at',
    ];

    protected function casts(): array
    {
        return [
            'preserved_data' => 'array',
            'transitioned_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
