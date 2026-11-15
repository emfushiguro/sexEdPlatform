<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleFeedback extends Model
{
    use HasFactory;

    protected $table = 'module_feedback';

    protected $fillable = [
        'module_id',
        'learner_id',
        'rating',
        'review_html',
        'instructor_reply_html',
        'submitted_at',
        'last_edited_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'submitted_at' => 'datetime',
            'last_edited_at' => 'datetime',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function learner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'learner_id');
    }

    protected function reviewHtml(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => \App\Support\SafeUserHtml::clean($value),
            set: fn ($value) => \App\Support\SafeUserHtml::clean($value),
        );
    }

    protected function instructorReplyHtml(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => \App\Support\SafeUserHtml::clean($value),
            set: fn ($value) => \App\Support\SafeUserHtml::clean($value),
        );
    }
}
