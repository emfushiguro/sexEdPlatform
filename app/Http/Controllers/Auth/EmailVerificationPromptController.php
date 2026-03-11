<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Schoolees\Psgc\Models\City;
use Schoolees\Psgc\Models\Barangay;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            if ($user->hasCompletedProfile()) {
                return redirect()->route('learner.dashboard');
            }

            // Verified but profile not yet complete — show inline profile form
            $learnerProfile = $user->learnerProfile;
            $cities = City::where('province_code', '402100000')->orderBy('name')->get();
            $barangays = collect();
            if ($learnerProfile && $learnerProfile->city_code) {
                $barangays = Barangay::where('city_code', $learnerProfile->city_code)
                    ->orderBy('name')
                    ->get();
            }

            return view('auth.verify-email', [
                'showSuccess'    => true,
                'learnerProfile' => $learnerProfile,
                'cities'         => $cities,
                'barangays'      => $barangays,
            ]);
        }

        return view('auth.verify-email', [
            'showSuccess'    => false,
            'learnerProfile' => null,
            'cities'         => collect(),
            'barangays'      => collect(),
        ]);
    }
}
