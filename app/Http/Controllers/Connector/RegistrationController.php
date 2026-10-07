<?php

namespace App\Http\Controllers\Connector;

use App\Http\Controllers\Controller;
use App\Http\Requests\Connector\ResubmitConnectorVerificationRequest;
use App\Http\Requests\Connector\StoreConnectorRequest;
use App\Models\Connector;
use App\Models\User;
use App\Notifications\Connectors\ConnectorApplicationWithdrawnNotification;
use App\Services\Connectors\ConnectorRegistrationService;
use App\Services\Connectors\ConnectorVerificationService;
use App\Support\ConnectorOptions;
use App\Support\ConnectorVerificationRequirements;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function create(ConnectorVerificationRequirements $verificationRequirements): View
    {
        $organizationTypes = $verificationRequirements->organizationTypes();
        $requirementCatalog = collect(config('connector_verification.requirements', []))
            ->mapWithKeys(static function (array $definition, string $key): array {
                return [$key => [
                    'key' => $key,
                    'label' => (string) ($definition['label'] ?? str($key)->headline()),
                    'guidance' => (string) ($definition['guidance'] ?? ''),
                    'required' => (bool) ($definition['required'] ?? false),
                    'minimum_evidence_count' => max(1, (int) ($definition['minimum_evidence_count'] ?? 1)),
                    'maximum_evidence_count' => max(1, (int) ($definition['maximum_evidence_count'] ?? 1)),
                    'rule_group' => (string) ($definition['rule_group'] ?? $key),
                    'metadata_fields' => array_values(array_intersect(
                        ['issuer', 'document_number', 'issue_date', 'expiry_date'],
                        array_values($definition['metadata_fields'] ?? []),
                    )),
                    'accepted_mime_types' => array_values($definition['accepted_mime_types'] ?? [
                        'application/pdf',
                        'image/jpeg',
                        'image/png',
                        'image/webp',
                    ]),
                    'max_kilobytes' => min(10240, max(1, (int) ($definition['max_kilobytes'] ?? 10240))),
                    'issuing_authority' => $definition['issuing_authority'] ?? null,
                    'source_url' => $definition['source_url'] ?? null,
                ]];
            })
            ->all();

        $initialRequirementKeys = [];
        if (old('category') && old('organization_type')) {
            try {
                $initialRequirementKeys = array_column(
                    $verificationRequirements->requirementsFor(old('category'), old('organization_type')),
                    'key',
                );
            } catch (\InvalidArgumentException) {
                $initialRequirementKeys = [];
            }
        }

        $cities = DB::table(config('psgc.tables.cities', 'cities'))
            ->where(function ($query): void {
                $query->where('province_code', ConnectorOptions::CAVITE_PROVINCE_CODE)
                    ->orWhere('code', 'like', ConnectorOptions::CAVITE_CITY_CODE_PREFIX.'%');
            })
            ->orderBy('name')
            ->get(['code', 'name']);

        $selectedCity = old('city_code');
        $barangays = $selectedCity
            ? DB::table(config('psgc.tables.barangays', 'barangays'))
                ->where('city_code', $selectedCity)
                ->orderBy('name')
                ->get(['code', 'name', 'city_code'])
            : collect();

        return view('connectors.register', [
            'categories' => ConnectorOptions::categories(),
            'organizationTypes' => $organizationTypes,
            'requirementCatalog' => $requirementCatalog,
            'initialRequirementKeys' => $initialRequirementKeys,
            'cities' => $cities,
            'barangays' => $barangays,
        ]);
    }

    public function store(StoreConnectorRequest $request, ConnectorRegistrationService $service): RedirectResponse
    {
        $connector = $service->register($request->user(), $request->validated());

        return redirect()->route('connector.status', $connector)
            ->with('success', 'Connector registration submitted for review.');
    }

    public function status(Request $request, Connector $connector): View
    {
        $isApplicant = $connector->created_by === $request->user()->id
            || $connector->primary_representative_user_id === $request->user()->id;

        abort_unless(
            $isApplicant
                || $connector->memberships()->where('user_id', $request->user()->id)->exists(),
            403,
        );

        $relations = ['memberships.role', 'reviews'];
        if ($isApplicant) {
            $relations[] = 'verificationRequirements.documents';
        }

        $connector->load($relations);
        $requirements = $isApplicant
            ? $connector->verificationRequirements->whereNull('superseded_at')->values()
            : collect();
        $canResubmit = $isApplicant && $this->canResubmit($connector);
        $eligibleRequirements = $canResubmit
            ? $requirements->filter(fn ($requirement): bool => in_array($requirement->status, [
                'rejected',
                'expired',
            ], true) || (
                $requirement->status === 'pending'
                && $requirement->requested_by !== null
            ))->values()
            : collect();
        $reviewHistory = $connector->reviews
            ->sortByDesc('reviewed_at')
            ->map(fn ($review): array => [
                'status' => $review->to_status,
                'reason' => $review->reason,
                'reviewed_at' => $review->reviewed_at,
            ])
            ->values();
        $logoUrl = $connector->status === 'verified' && $connector->logo_path
            ? Storage::disk((string) config('connector_verification.logo_disk', 'public'))->url($connector->logo_path)
            : null;

        return view('connectors.status', [
            'connector' => $connector,
            'isApplicant' => $isApplicant,
            'canResubmit' => $canResubmit,
            'requirements' => $requirements,
            'eligibleRequirements' => $eligibleRequirements,
            'reviewHistory' => $reviewHistory,
            'logoUrl' => $logoUrl,
            'nextAction' => $this->nextAction($connector, $isApplicant, $canResubmit),
        ]);
    }

    public function resubmit(
        ResubmitConnectorVerificationRequest $request,
        Connector $connector,
        ConnectorVerificationService $service,
    ): RedirectResponse {
        $validated = $request->validated();

        $service->resubmit(
            $connector,
            $request->user(),
            $validated,
            (int) $validated['submission_round'],
        );

        return redirect()->route('connector.status', $connector)
            ->with('success', 'Verification evidence submitted for review.');
    }

    private function canResubmit(Connector $connector): bool
    {
        if (in_array($connector->status, ['withdrawn', 'suspended'], true)
            || in_array($connector->verification_status, [
                Connector::VERIFICATION_UNDER_REVIEW,
                Connector::VERIFICATION_REJECTED,
            ], true)) {
            return false;
        }

        return $connector->verification_status === Connector::VERIFICATION_RESUBMISSION_REQUIRED
            || ($connector->status === 'verified'
                && $connector->verification_status === Connector::VERIFICATION_REVERIFICATION_REQUIRED)
            || ($connector->status === 'pending' && $connector->verification_status === null);
    }

    private function nextAction(Connector $connector, bool $isApplicant, bool $canResubmit): string
    {
        if (! $isApplicant) {
            return 'Contact the connector representative for application updates.';
        }

        if ($canResubmit) {
            return 'Replace the evidence marked for resubmission, then submit it for review.';
        }

        return match ($connector->verification_status) {
            Connector::VERIFICATION_UNDER_REVIEW => 'Your verification evidence is under review.',
            Connector::VERIFICATION_REVERIFICATION_REQUIRED => 'Reverification is required before workspace access continues.',
            Connector::VERIFICATION_REJECTED => 'This verification decision is final.',
            default => match ($connector->status) {
                'verified' => 'Your connector is verified and ready for workspace use.',
                'withdrawn' => 'This application was withdrawn.',
                'suspended' => 'Contact support about this suspended connector.',
                default => 'Your connector application is waiting for review.',
            },
        };
    }

    public function withdraw(Request $request, Connector $connector): RedirectResponse
    {
        abort_unless(
            $connector->created_by === $request->user()->id
                || $connector->primary_representative_user_id === $request->user()->id,
            403
        );

        abort_unless($connector->status === 'pending', 403);

        DB::transaction(function () use ($request, $connector): void {
            $from = $connector->status;

            $connector->update([
                'status' => 'withdrawn',
                'reviewed_by' => null,
                'reviewed_at' => null,
                'rejection_reason' => null,
            ]);

            $connector->memberships()
                ->where('status', 'pending')
                ->update(['status' => 'removed', 'removed_at' => now()]);

            $connector->reviews()->create([
                'reviewed_by' => $request->user()->id,
                'from_status' => $from,
                'to_status' => 'withdrawn',
                'reason' => 'Connector application withdrawn by applicant.',
                'reviewed_at' => now(),
            ]);
        });

        $this->adminRecipients()->each(
            fn (User $admin) => $admin->notify(new ConnectorApplicationWithdrawnNotification($connector->fresh()))
        );

        return redirect()->route('connector.status', $connector)
            ->with('success', 'Connector application withdrawn.');
    }

    private function adminRecipients()
    {
        return User::query()
            ->where('role', 'admin')
            ->orWhereHas('roles', fn ($query) => $query->where('name', 'admin'))
            ->get();
    }
}
