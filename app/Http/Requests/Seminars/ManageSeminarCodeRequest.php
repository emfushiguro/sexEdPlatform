<?php

namespace App\Http\Requests\Seminars;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Throwable;

class ManageSeminarCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function validationData(): array
    {
        $data = parent::validationData();
        foreach (['attendance_start_at', 'attendance_end_at'] as $field) {
            if (blank($data[$field] ?? null)) {
                continue;
            }
            try {
                $data[$field] = Carbon::parse($data[$field], config('app.display_timezone'))->utc()->format('Y-m-d H:i:s');
            } catch (Throwable) {
                // The date rule reports malformed input.
            }
        }

        return $data;
    }

    public function rules(): array
    {
        return [
            'attendance_start_at' => ['nullable', 'date'],
            'attendance_end_at' => ['nullable', 'date', 'after:attendance_start_at'],
        ];
    }
}
