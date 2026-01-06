<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleAttachment extends Model
{
    protected $fillable = [
        'module_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'is_premium',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'is_premium' => 'boolean',
        ];
    }

    // Relationships

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    // Scopes

    public function scopePremium($query)
    {
        return $query->where('is_premium', true);
    }

    public function scopeFree($query)
    {
        return $query->where('is_premium', false);
    }

    // Helper Methods

    public function getFileSizeFormatted(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
