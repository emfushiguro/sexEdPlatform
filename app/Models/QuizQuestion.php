<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    protected $fillable = [
        'quiz_id',
        'question_text',
        'question_type',
        'points',
        'order',
        'acceptable_answers',
        'case_sensitive',
        'word_bank',
        'image_path',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'order' => 'integer',
            'case_sensitive' => 'boolean',
            'word_bank' => 'array',
        ];
    }

    // Relationships

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function options()
    {
        return $this->hasMany(QuizOption::class)->orderBy('order');
    }

    public function correctOptions()
    {
        return $this->hasMany(QuizOption::class)->where('is_correct', true);
    }
}
