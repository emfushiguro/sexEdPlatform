<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SaveIdentityReferenceRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasRole('admin') ?? false; }
    public function rules(): array
    {
        return [
            'issuer' => ['nullable', 'string', 'max:120'],
            'version' => ['nullable', 'string', 'max:120'],
            'published_on' => ['nullable', 'date', 'before_or_equal:today'],
            'source_url' => ['required', 'url:https', 'max:2048'],
            'verification_url' => ['nullable', 'url:https', 'max:2048'],
            'image' => [$this->route('example') ? 'sometimes' : 'required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
