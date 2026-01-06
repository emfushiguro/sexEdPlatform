<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizOption extends Model
{
    protected $fillable = [
        'quiz_question_id',
        'option_text',
        'is_correct',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'order' => 'integer',
        ];
    }

    // Relationships

    public function quizQuestion()
    {
        return $this->belongsTo(QuizQuestion::class);
    }
}
