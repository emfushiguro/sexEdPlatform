<?php

namespace App\Services\Admin;

use App\Models\AdminCreatorProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AdminCreatorProfileService
{
    public function getOrCreateForUser(User $user): AdminCreatorProfile
    {
        return AdminCreatorProfile::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'public_display_name' => $user->full_name,
                'affiliation' => 'Conscious Connections Team',
                'show_individual_attribution' => false,
            ]
        );
    }

    /**
     * @param array<string, mixed> $validated
     */
    public function updateFromValidatedPayload(User $user, array $validated, ?UploadedFile $avatar = null): AdminCreatorProfile
    {
        $profile = $this->getOrCreateForUser($user);

        if ($avatar instanceof UploadedFile) {
            if (!empty($profile->avatar_path)) {
                Storage::disk('public')->delete((string) $profile->avatar_path);
            }

            $validated['avatar_path'] = $avatar->store('admin-creator-avatars', 'public');
        }

        $profile->fill([
            'public_display_name' => (string) ($validated['public_display_name'] ?? $profile->public_display_name),
            'bio' => $validated['bio'] ?? null,
            'affiliation' => (string) ($validated['affiliation'] ?? $profile->affiliation),
            'show_individual_attribution' => (bool) ($validated['show_individual_attribution'] ?? false),
            'avatar_path' => $validated['avatar_path'] ?? $profile->avatar_path,
        ]);

        $profile->save();

        return $profile;
    }
}
