<?php

namespace App\Http\Requests;

use App\Enums\ApprovalStatus;
use App\Enums\ClinicService;
use App\Enums\ClinicType;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClinicRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization should be handled in middleware or controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'type' => ['sometimes', 'required', 'string', 'in:' . implode(',', ClinicType::values())],
            'city' => 'sometimes|required|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'address' => 'sometimes|required|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'contact' => 'nullable|string|max:255|regex:/^[\+]?[0-9\s\-\(\)]+$/',
            'email' => 'nullable|email|max:255',
            'services' => 'nullable|array',
            'services.*' => ['string', 'in:' . implode(',', ClinicService::values())],
            'operating_hours' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'approval_status' => ['nullable', 'string', 'in:' . implode(',', ApprovalStatus::values())],
            'is_active' => 'boolean',
            'verified' => 'boolean',
            'is_premium' => 'boolean',
            'rejection_reason' => 'nullable|string|max:1000|required_if:approval_status,rejected',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'clinic name',
            'type' => 'clinic type',
            'city' => 'city',
            'barangay' => 'barangay',
            'address' => 'address',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'contact' => 'contact number',
            'email' => 'email address',
            'services' => 'available services',
            'operating_hours' => 'operating hours',
            'notes' => 'additional notes',
            'is_active' => 'active status',
            'verified' => 'verified status',
            'is_premium' => 'premium status',
            'approval_status' => 'approval status',
            'rejection_reason' => 'rejection reason',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'services.*.in' => 'The selected service is invalid. Please choose from the available options.',
            'operating_hours.*.open.date_format' => 'Opening time must be in HH:MM format (e.g., 08:00)',
            'operating_hours.*.close.date_format' => 'Closing time must be in HH:MM format (e.g., 17:00)',
            'rejection_reason.required_if' => 'A rejection reason is required when rejecting a clinic.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert boolean strings to actual booleans
        if ($this->has('is_active')) {
            $this->merge(['is_active' => $this->boolean('is_active')]);
        }
        if ($this->has('verified')) {
            $this->merge(['verified' => $this->boolean('verified')]);
        }
        if ($this->has('is_premium')) {
            $this->merge(['is_premium' => $this->boolean('is_premium')]);
        }
    }
}
