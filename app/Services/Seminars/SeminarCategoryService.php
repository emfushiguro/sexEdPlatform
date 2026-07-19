<?php

namespace App\Services\Seminars;

use App\Models\Seminar;

class SeminarCategoryService
{
    public function normalizeCustomCategory(?string $category, ?string $customCategory): ?string
    {
        if ($category !== 'other') {
            return null;
        }

        $value = trim((string) $customCategory);

        return $value === '' ? null : $value;
    }

    public function displayName(Seminar $seminar): string
    {
        if ($seminar->category === 'other' && filled($seminar->custom_category)) {
            return $seminar->custom_category;
        }

        return config('seminars.categories.'.$seminar->category, ucfirst((string) $seminar->category));
    }
}
