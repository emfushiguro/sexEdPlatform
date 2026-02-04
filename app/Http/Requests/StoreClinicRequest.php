<?php

namespace App\Http\Requests;

use App\Enums\ClinicService;
use App\Enums\ClinicType;
use Illuminate\Foundation\Http\FormRequest;

class StoreClinicRequest extends FormRequest
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
            'user_id' => 'sometimes|nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'type' => ['required', 'string', 'in:' . implode(',', ClinicType::values())],
            'city' => 'required|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'barangay_code' => 'nullable|string|max:255',
            'address' => 'required|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'contact' => 'nullable|string|max:255|regex:/^[\+]?[0-9\s\-\(\)]+$/',
            'email' => 'nullable|email|max:255',
            'services' => 'nullable|array',
            'services.*' => ['string', 'in:' . implode(',', ClinicService::values())],
            'operating_hours' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'approval_status' => 'nullable|string|in:pending,approved,rejected',
            'is_active' => 'boolean',
            'verified' => 'boolean',
            'is_premium' => 'boolean',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'user_id' => 'clinic owner',
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
            'notes' => 'notes',
            'approval_status' => 'approval status',
            'is_active' => 'active status',
            'verified' => 'verified',
            'is_premium' => 'premium status',
        ];
    }
}
