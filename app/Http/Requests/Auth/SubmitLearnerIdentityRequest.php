<?php

namespace App\Http\Requests\Auth;

use App\Models\LearnerIdentityVerification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SubmitLearnerIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user?->isLearner() || $user->isParentRegistration() || ! $user->hasVerifiedEmail()) {
            return false;
        }

        $case = $this->currentCase();

        return $case !== null
            && $case->pathway === ($user->deriveAgeBracketCache() === 'teens' ? 'teen' : 'adult')
            && in_array($case->status, [null, 'rejected'], true);
    }

    public function rules(): array
    {
        $case = $this->currentCase();
        $type = (string) $this->input('document_type');
        $subtype = (string) $this->input('government_id_type');
        $governmentTypes = config('guardian_identity.id_types', []);
        $requiresBack = $type === 'government_id' && (bool) data_get($governmentTypes, $subtype.'.requires_back', false);
        $documentChanged = $case?->document_type !== $type
            || ($type === 'government_id' && $case?->government_id_type !== $subtype);
        $image = ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=320,min_height=320,max_width=6000,max_height=6000'];
        $types = $case?->pathway === 'teen'
            ? ['school_id', 'institution_id', 'government_id']
            : ['government_id'];

        return [
            'document_type' => ['required', Rule::in($types)],
            'government_id_type' => [$type === 'government_id' ? 'required' : 'prohibited', Rule::in(array_keys($governmentTypes))],
            'government_id_type_other' => [$subtype === 'other' && $type === 'government_id' ? 'required' : 'prohibited', 'string', 'max:80'],
            'identity_front' => [$documentChanged || ! $this->hasValidSlot($case, 'identity_front') ? 'required' : 'sometimes', ...$image],
            'identity_back' => [$requiresBack
                ? ($documentChanged || ! $this->hasValidSlot($case, 'identity_back') ? 'required' : 'sometimes')
                : 'prohibited', ...$image],
            'selfie' => [! $this->hasValidSlot($case, 'selfie') ? 'required' : 'sometimes', ...$image],
            'confirm_submission' => ['accepted'],
        ];
    }

    private function currentCase(): ?LearnerIdentityVerification
    {
        return $this->user()?->identityVerifications()->whereNull('superseded_at')->first();
    }

    private function hasValidSlot(?LearnerIdentityVerification $case, string $slot): bool
    {
        $evidence = $case?->evidence()->where('slot', $slot)->first();

        return $evidence !== null
            && $evidence->byte_size > 0
            && $evidence->width >= 320 && $evidence->width <= 6000
            && $evidence->height >= 320 && $evidence->height <= 6000
            && in_array($evidence->mime_type, ['image/jpeg', 'image/png', 'image/webp'], true)
            && Storage::disk('local')->exists($evidence->storage_path);
    }
}
