<?php

namespace App\Http\Controllers\InstructorApplication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreInstructorApplicantPersonalInformationRequest;
use App\Models\InstructorIdentityVerification;
use App\Models\User;
use App\Services\Location\CaviteAddressResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Schoolees\Psgc\Models\Barangay;
use Schoolees\Psgc\Models\City;

class PersonalInformationController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $this->applicant($request->user());
        $profile = $user->load('profile')->profile;
        $case = $user->instructorIdentityVerifications()->current()->latest('submission_round')->first();
        $barangays = $profile?->city_code
            ? Barangay::query()->where('city_code', $profile->city_code)->orderBy('name')->get(['code', 'name'])
            : collect();

        return view('instructor-application.personal-information', [
            'user' => $user,
            'correctionRequested' => $this->canCorrect($case),
            'barangays' => $barangays,
            'cities' => City::query()
                ->where('province_code', config('instructor_application.province_code'))
                ->orderBy('name')
                ->get(['code', 'name']),
        ]);
    }

    public function update(
        StoreInstructorApplicantPersonalInformationRequest $request,
        CaviteAddressResolver $addressResolver,
    ): RedirectResponse {
        $user = $this->applicant($request->user());

        $validated = $request->validated();
        $address = $addressResolver->resolve($validated['city_code'], $validated['barangay_code']);

        $updated = DB::transaction(function () use ($user, $validated, $address): bool {
            $user = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $case = $user->instructorIdentityVerifications()->current()->latest('submission_round')->lockForUpdate()->first();
            if (($case && ! $this->canCorrect($case))
                || (! $case && $user->instructorIdentityVerifications()->exists())) {
                return false;
            }

            $user->update([
                'name' => trim($validated['first_name'].' '.($validated['middle_initial'] ?? '').' '.$validated['last_name'].' '.($validated['suffix'] ?? '')),
                'first_name' => $validated['first_name'],
                'middle_initial' => $validated['middle_initial'] ?? null,
                'last_name' => $validated['last_name'],
                'suffix' => $validated['suffix'] ?? null,
                'birthdate' => $validated['birthdate'],
                'age' => now()->parse($validated['birthdate'])->age,
            ]);
            $user->profile()->updateOrCreate(['user_id' => $user->id], [
                'birthdate' => $validated['birthdate'],
                'gender' => $validated['gender'] ?? null,
                'contact' => $validated['contact'] ?? null,
                'location' => $address['city_name'].', '.$address['province_name'],
                'province_code' => $address['province_code'],
                'city_code' => $address['city_code'],
                'barangay_code' => $address['barangay_code'],
                'address_line' => $validated['address_line'] ?? null,
            ]);

            if ($case) {
                $case->audits()->create([
                    'actor_id' => $user->id,
                    'action' => 'personal_information_corrected',
                    'from_status' => $case->status,
                    'to_status' => $case->status,
                    'submission_round' => $case->submission_round,
                ]);
            }

            return true;
        });

        if (! $updated) {
            return back()->withErrors(['personal_information' => 'Personal information is locked while identity verification is in progress or under review.']);
        }

        return back()->with('success', 'Personal information updated.');
    }

    private function canCorrect(?InstructorIdentityVerification $case): bool
    {
        return $case !== null
            && $case->status === InstructorIdentityVerification::STATUS_ACTION_REQUIRED
            && $case->resubmission_allowed
            && in_array('clarify_personal_information', $case->requested_action_codes ?? [], true);
    }

    private function applicant(User $user): User
    {
        abort_unless($user->isInstructorApplicant(), 403);

        return $user;
    }
}
