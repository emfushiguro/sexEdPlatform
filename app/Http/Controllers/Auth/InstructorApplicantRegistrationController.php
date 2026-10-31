<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreInstructorApplicantAccountRequest;
use App\Http\Requests\Auth\StoreInstructorApplicantPersonalInformationRequest;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Admin\RoleSyncService;
use App\Services\Location\CaviteAddressResolver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Schoolees\Psgc\Models\Barangay;
use Schoolees\Psgc\Models\City;

class InstructorApplicantRegistrationController extends Controller
{
    public function createPersonal(): View
    {
        $cities = City::query()
            ->where('province_code', config('instructor_application.province_code'))
            ->orderBy('name')
            ->get(['code', 'name']);
        $selectedCity = session('pending_instructor_applicant_personal.city_code');
        $barangays = $selectedCity
            ? Barangay::query()->where('city_code', $selectedCity)->orderBy('name')->get(['code', 'name'])
            : collect();

        return view('auth.instructor-applicant-register-personal', [
            'cities' => $cities,
            'barangays' => $barangays,
            'personalInfo' => session('pending_instructor_applicant_personal', []),
        ]);
    }

    public function storePersonal(
        StoreInstructorApplicantPersonalInformationRequest $request,
        CaviteAddressResolver $addressResolver,
    ): RedirectResponse {
        $validated = $request->validated();
        $address = $addressResolver->resolve($validated['city_code'], $validated['barangay_code']);

        session()->put('pending_instructor_applicant_personal', array_merge($validated, $address));

        return redirect()->route('instructor-application.register.account');
    }

    public function createAccount(): View|RedirectResponse
    {
        if (! session('pending_instructor_applicant_personal')) {
            return redirect()->route('instructor-application.register.personal');
        }

        return view('auth.instructor-applicant-register-account');
    }

    public function storeAccount(
        StoreInstructorApplicantAccountRequest $request,
        RoleSyncService $roleSyncService,
    ): RedirectResponse {
        $personal = session('pending_instructor_applicant_personal');

        if (! is_array($personal)) {
            return redirect()->route('instructor-application.register.personal');
        }

        $account = $request->validated();

        $user = DB::transaction(function () use ($personal, $account, $roleSyncService): User {
            $nameParts = array_filter([
                $personal['first_name'],
                $personal['middle_initial'] ?? null,
                $personal['last_name'],
                $personal['suffix'] ?? null,
            ]);

            $user = User::create([
                'name' => implode(' ', $nameParts),
                'first_name' => $personal['first_name'],
                'middle_initial' => $personal['middle_initial'] ?? null,
                'last_name' => $personal['last_name'],
                'suffix' => $personal['suffix'] ?? null,
                'email' => $account['email'],
                'birthdate' => $personal['birthdate'],
                'age' => now()->parse($personal['birthdate'])->age,
                'password' => Hash::make($account['password']),
                'role' => User::ACCOUNT_TYPE_APPLICANT,
                'account_type' => User::ACCOUNT_TYPE_APPLICANT,
                'status' => User::STATUS_ACTIVE,
            ]);

            UserProfile::create([
                'user_id' => $user->id,
                'birthdate' => $personal['birthdate'],
                'gender' => $personal['gender'] ?? null,
                'contact' => $personal['contact'] ?? null,
                'location' => $personal['city_name'].', '.$personal['province_name'],
                'province_code' => $personal['province_code'],
                'city_code' => $personal['city_code'],
                'barangay_code' => $personal['barangay_code'],
                'address_line' => $personal['address_line'] ?? null,
            ]);

            $roleSyncService->assignPrimaryRole($user, User::ACCOUNT_TYPE_APPLICANT);
            $user->refreshClassificationCache();

            return $user;
        });

        try {
            event(new Registered($user));
        } catch (\Throwable) {
            Log::warning('Instructor applicant verification dispatch failed.', ['user_id' => $user->id]);
        }

        session()->forget('pending_instructor_applicant_personal');
        Auth::login($user);

        return redirect()->route('verification.notice')
            ->with('success', 'Applicant account created. Please verify your email address.');
    }
}
