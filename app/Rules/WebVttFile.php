<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

final class WebVttFile implements ValidationRule
{
    private const MIMES = ['text/vtt', 'text/plain'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('The :attribute must be a valid WebVTT file.');

            return;
        }

        if (strtolower($value->getClientOriginalExtension()) !== 'vtt') {
            $fail('The :attribute must use the .vtt extension.');

            return;
        }

        if (! in_array($value->getMimeType(), self::MIMES, true)) {
            $fail('The :attribute must be a WebVTT text file.');

            return;
        }

        $contents = file_get_contents($value->getRealPath());
        if ($contents === false
            || str_contains($contents, "\0")
            || ! mb_check_encoding($contents, 'UTF-8')) {
            $fail('The :attribute must contain valid UTF-8 WebVTT text.');

            return;
        }

        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;
        if (! preg_match('/\AWEBVTT(?:[ \t][^\r\n]*)?\r?\n/', $contents)) {
            $fail('The :attribute must begin with a WEBVTT header.');

            return;
        }

        $timestamp = '(?:[0-9]{2,}:)?[0-9]{2}:[0-9]{2}\.[0-9]{3}';
        if (! preg_match('/^'.$timestamp.'[ \t]+-->[ \t]+'.$timestamp.'(?:[ \t].*)?$/m', $contents)) {
            $fail('The :attribute must contain at least one WebVTT cue.');
        }
    }
}
