<?php

namespace App\Services\InstructorApplication;

use App\Models\User;

final class InstructorApplicantPersonalInformation
{
    /**
     * @return array<string, string|null>
     */
    public function for(User $user): array
    {
        $user->loadMissing([
            'profile.city',
            'profile.barangay',
            'learnerProfile.city',
            'learnerProfile.barangay',
        ]);

        $profile = $user->isLearner() ? $user->learnerProfile : $user->profile;
        $cityName = $profile?->city?->name;
        $barangayName = $profile?->barangay?->name ?? ($user->isLearner() ? $profile?->barangay : null);

        return [
            'first_name' => $user->first_name,
            'middle_initial' => $user->middle_initial,
            'last_name' => $user->last_name,
            'suffix' => $user->suffix,
            'birthdate' => $user->birthdate?->format('Y-m-d'),
            'province_name' => 'Cavite',
            'city_name' => $cityName,
            'barangay_name' => $barangayName,
        ];
    }
}
