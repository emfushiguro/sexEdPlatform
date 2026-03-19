<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Certificate extends Model
{
    protected $fillable = [
        'user_id',
        'module_id',
        'certificate_number',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
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

    // Boot method

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($certificate) {
            if (empty($certificate->issued_at)) {
                $certificate->issued_at = now();
            }

            if (empty($certificate->certificate_number)) {
                $certificate->certificate_number = static::generateCertificateNumber($certificate->issued_at);
            }
        });
    }

    // Helper Methods

    public static function generateCertificateNumber($issuedAt = null): string
    {
        $year = now()->format('Y');

        if (!empty($issuedAt)) {
            $timestamp = strtotime((string) $issuedAt);

            if ($timestamp !== false) {
                $year = date('Y', $timestamp);
            }
        }

        return 'CC-' . $year . '-' . strtoupper(Str::random(8));
    }
}
