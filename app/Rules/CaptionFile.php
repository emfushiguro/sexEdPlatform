<?php

namespace App\Rules;

use App\Support\SubRipCaption;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

final class CaptionFile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('The :attribute must be a valid caption file.');

            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());
        if ($extension === 'vtt') {
            (new WebVttFile)->validate($attribute, $value, $fail);

            return;
        }

        if ($extension !== 'srt') {
            $fail('The :attribute must use the .vtt or .srt extension.');

            return;
        }

        if (! in_array($value->getMimeType(), ['text/plain', 'text/srt', 'application/srt', 'application/x-srt', 'application/x-subrip'], true)) {
            $fail('The :attribute must be an SRT text file.');

            return;
        }

        $contents = file_get_contents($value->getRealPath());
        if ($contents === false) {
            $fail('The :attribute could not be read.');

            return;
        }

        try {
            SubRipCaption::toWebVtt($contents);
        } catch (InvalidArgumentException $exception) {
            $fail($exception->getMessage());
        }
    }
}
