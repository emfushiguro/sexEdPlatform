<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

    public function getImageUrlAttribute(): ?string
    {
        return $this->resolvePublicMediaUrl($this->image_path, 'quiz-images');
    }

    private function resolvePublicMediaUrl(?string $path, string $defaultDirectory = ''): ?string
    {
        if (!$path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        $normalized = ltrim($path, '/');

        if (Str::startsWith($normalized, 'storage/')) {
            $normalized = substr($normalized, 8);
        }

        if (!str_contains($normalized, '/') && $defaultDirectory !== '') {
            $normalized = trim($defaultDirectory, '/') . '/' . $normalized;
        }

        return Storage::url($normalized);
    }
}
