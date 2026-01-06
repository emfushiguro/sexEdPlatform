<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleEnrollment extends Model
{
    protected $fillable = [
        'user_id',
        'module_id',
        'enrolled_at',
        'completed_at',
        'completion_percentage',
    ];

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
            'completed_at' => 'datetime',
            'completion_percentage' => 'integer',
        ];
    }

    // Relationships

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    // Scopes

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }

    public function scopeInProgress($query)
    {
        return $query->whereNull('completed_at')->where('completion_percentage', '>', 0);
    }

    // Helper Methods

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}
