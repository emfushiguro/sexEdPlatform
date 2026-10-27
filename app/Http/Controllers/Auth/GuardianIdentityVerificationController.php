<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreGuardianIdentityVerificationRequest;
use App\Services\ParentChildVerificationService;
use App\Services\Persona\PersonaInquiryService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class GuardianIdentityVerificationController extends Controller
{
    public function create(Request $request, PersonaInquiryService $personaInquiryService): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isParentRegistration()) {
            return redirect()->route('learner.dashboard');
        }

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        if ($user->isParentVerificationPending() || $user->isParentVerificationApproved()) {
            return redirect()->route('guardian.verification.status');
        }

        return view('auth.guardian-verification', [
            'idTypes' => $this->idTypes(),
            'idTypeRequirements' => config('guardian_identity.id_types', []),
            'personaRequired' => $personaInquiryService->isRequired(),
            'personaAttempt' => $personaInquiryService->isRequired()
                ? $personaInquiryService->currentAttempt($user)
                : null,
        ]);
    }

    public function store(
        StoreGuardianIdentityVerificationRequest $request,
        ParentChildVerificationService $verificationService,
        PersonaInquiryService $personaInquiryService,
    ): RedirectResponse {
        $idType = (string) $request->string('government_id_type');

        if ($personaInquiryService->isRequired()) {
            return redirect()->route('guardian.verification.status')
                ->withErrors(['persona' => 'Submit your government ID and selfie once through Persona.']);
        }

        $storedPaths = [];

        try {
            $frontPath = $request->file('government_id_front')->store(
                'guardian-verifications/'.$request->user()->id,
                'local'
            );
            $storedPaths[] = $frontPath;

            $backPath = $request->hasFile('government_id_back')
                ? $request->file('government_id_back')->store('guardian-verifications/'.$request->user()->id, 'local')
                : null;
            $storedPaths[] = $backPath;

            $verificationService->submitParentIdentity(
                parent: $request->user(),
                idType: $idType,
                idTypeOther: $request->filled('government_id_type_other') ? (string) $request->string('government_id_type_other') : null,
                frontPath: (string) $frontPath,
                backPath: $backPath,
            );
        } catch (DomainException $exception) {
            Storage::disk('local')->delete(array_filter($storedPaths));

            return back()->withErrors(['persona' => $exception->getMessage()])->withInput();
        } catch (Throwable $exception) {
            Storage::disk('local')->delete(array_filter($storedPaths));

            throw $exception;
        }

        return redirect()->route('guardian.verification.status')
            ->with('success', 'Your Guardian verification has been submitted successfully.');
    }

    public function status(Request $request, PersonaInquiryService $personaInquiryService): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isParentRegistration()) {
            return redirect()->route('learner.dashboard');
        }

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        if (! $user->parent_verification_status) {
            return redirect()->route('guardian.verification.create');
        }

        if ($user->isParentVerificationApproved() && ! $user->hasCompletedGuardianOnboarding()) {
            return redirect()->route('guardian.onboarding.show');
        }

        return view('auth.parent-verification-status', [
            'user' => $user,
            'isApproved' => $user->isParentVerificationApproved(),
            'personaRequired' => $personaInquiryService->isRequired(),
            'personaAttempt' => $personaInquiryService->isRequired()
                ? $personaInquiryService->currentAttempt($user)
                : null,
        ]);
    }

    private function idTypes(): array
    {
        return collect(config('guardian_identity.id_types', []))
            ->mapWithKeys(fn (array $meta, string $value): array => [$value => (string) ($meta['label'] ?? $value)])
            ->all();
    }
}
