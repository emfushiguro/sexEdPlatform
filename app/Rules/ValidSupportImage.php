<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidSupportImage implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value || ! method_exists($value, 'getRealPath')) {
            $fail('The attachment must be a valid image.');

            return;
        }
        $path = $value->getRealPath();
        $size = is_string($path) ? @getimagesize($path) : false;
        if ($size === false || ($size[0] ?? 0) < 1 || ($size[1] ?? 0) < 1 || ($size[0] ?? 0) > 10000 || ($size[1] ?? 0) > 10000) {
            $fail('The attachment must be a decodable image no larger than 10000 by 10000 pixels.');
        }
    }
}
