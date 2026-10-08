<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningPathModule extends Model
{
    protected $fillable = ['learning_path_id', 'module_id', 'position'];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    public function learningPath(): BelongsTo
    {
        return $this->belongsTo(LearningPath::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class)->withTrashed();
    }
}
