<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\InstructorFinancialReportFilterRequest;
use App\Models\InstructorEarningsVisibility;
use App\Models\Module;
use App\Models\ModuleSaleLedger;
use App\Models\User;
use App\Services\Finance\FinancialReportFilterNormalizer;
use App\Services\Finance\FinancialReportService;
use App\Services\Instructor\InstructorPlanCapabilityService;
use App\Services\Monetization\CommissionPolicyResolver;
use Illuminate\Http\Request;
use RuntimeException;

class ModuleEarningsController extends Controller
{
    public function __construct(
        private readonly CommissionPolicyResolver $commissionPolicyResolver,
        private readonly InstructorPlanCapabilityService $instructorPlanCapabilityService,
        private readonly FinancialReportService $financialReportService,
        private readonly FinancialReportFilterNormalizer $financialReportFilterNormalizer,
    ) {
    }

    public function index(InstructorFinancialReportFilterRequest $request)
    {
        $this->authorize('viewEarnings', Module::class);
        $this->ensureEarningsVisibilityAllowed($request->user());

        $instructorId = (int) $request->user()->id;

        $reportFilter = $this->financialReportFilterNormalizer->normalize(
            filters: $request->validated(),
            forcedInstructorId: $instructorId,
        );

        $earningsPayload = $this->financialReportService->getInstructorEarnings($reportFilter);
        $transactions = $this->financialReportService->getInstructorVisibleTransactions($reportFilter, 15);

        $summary = (array) ($earningsPayload['summary'] ?? []);
        $stats = [
            'total_sales' => (int) ($summary['total_transactions'] ?? 0),
            'gross_revenue' => (float) ($summary['gross_revenue'] ?? 0),
            'platform_commission' => (float) ($summary['platform_commission'] ?? 0),
            'net_earnings' => (float) ($summary['instructor_earnings'] ?? 0),
            'range_label' => ucfirst((string) $reportFilter->reportType),
        ];

        $effectiveCommissionPolicy = $this->resolveEffectiveCommissionPolicyPayload($request->user());

        return view('instructor.earnings.index', [
            'transactions' => $transactions,
            'stats' => $stats,
            'effectiveCommissionPolicy' => $effectiveCommissionPolicy,
            'moduleBreakdown' => $earningsPayload['module_breakdown'] ?? collect(),
            'reportFilter' => $reportFilter,
        ]);
    }

    public function show(Request $request, ModuleSaleLedger $moduleSaleLedger)
    {
        $this->authorize('viewEarnings', Module::class);
        $this->ensureEarningsVisibilityAllowed($request->user());

        $instructorId = (int) $request->user()->id;

        abort_unless((int) $moduleSaleLedger->instructor_id === $instructorId, 404);

        $isHidden = $moduleSaleLedger->visibility()
            ->where('instructor_id', $instructorId)
            ->whereNotNull('deleted_at')
            ->exists();

        abort_if($isHidden, 404);

        $moduleSaleLedger->load([
            'module:id,title,thumbnail,description,price_amount,price_currency',
            'learner:id,name,first_name,last_name,email',
            'learner.learnerProfile:id,user_id,avatar_path,username,barangay,barangay_code,city_code',
            'payment:id,transaction_id,paid_at,method,status',
            'modulePurchase:id,module_id,purchased_at,status',
        ]);

        return view('instructor.earnings.show', [
            'ledger' => $moduleSaleLedger,
            'effectiveCommissionPolicy' => $this->resolveEffectiveCommissionPolicyPayload($request->user()),
        ]);
    }

    public function destroyVisibility(Request $request, ModuleSaleLedger $moduleSaleLedger)
    {
        return $this->hideTransactionFromInstructor(
            request: $request,
            moduleSaleLedger: $moduleSaleLedger,
            defaultReason: 'hidden_by_instructor',
            successMessage: 'Sale row hidden from your earnings list.'
        );
    }

    public function archive(Request $request, ModuleSaleLedger $moduleSaleLedger)
    {
        return $this->hideTransactionFromInstructor(
            request: $request,
            moduleSaleLedger: $moduleSaleLedger,
            defaultReason: 'archived_by_instructor',
            successMessage: 'Transaction archived from your earnings list.'
        );
    }

    public function delete(Request $request, ModuleSaleLedger $moduleSaleLedger)
    {
        return $this->hideTransactionFromInstructor(
            request: $request,
            moduleSaleLedger: $moduleSaleLedger,
            defaultReason: 'deleted_by_instructor',
            successMessage: 'Transaction removed from your earnings list.'
        );
    }

    private function hideTransactionFromInstructor(
        Request $request,
        ModuleSaleLedger $moduleSaleLedger,
        string $defaultReason,
        string $successMessage,
    )
    {
        $this->authorize('viewEarnings', Module::class);
        $this->ensureEarningsVisibilityAllowed($request->user());

        $instructorId = (int) $request->user()->id;

        abort_unless((int) $moduleSaleLedger->instructor_id === $instructorId, 404);

        $deleteReason = $request->string('delete_reason')->toString();
        if ($deleteReason === '') {
            $deleteReason = $defaultReason;
        }

        InstructorEarningsVisibility::query()->updateOrCreate(
            [
                'module_sale_ledger_id' => $moduleSaleLedger->id,
                'instructor_id' => $instructorId,
            ],
            [
                'deleted_at' => now(),
                'deleted_by' => $instructorId,
                'delete_reason' => $deleteReason,
            ]
        );

        return redirect()->route('instructor.earnings.index')
            ->with('success', $successMessage);
    }

    private function resolveEffectiveCommissionPolicyPayload(?User $user): ?array
    {
        if (!$user) {
            return null;
        }

        try {
            $policy = $this->commissionPolicyResolver->resolveForInstructor((int) $user->id);

            return [
                'commission_percent' => (float) $policy->commission_percent,
                'tax_basis' => (string) $policy->tax_basis,
                'refund_policy' => (string) $policy->refund_policy,
            ];
        } catch (RuntimeException) {
            return null;
        }
    }

    private function ensureEarningsVisibilityAllowed(?User $user): void
    {
        abort_unless($user !== null, 403);

        if (!$this->instructorPlanCapabilityService->isStrictRolloutMode()) {
            return;
        }

        if (!$this->instructorPlanCapabilityService->canViewEarnings($user)) {
            abort(403, 'Your current instructor plan does not include earnings visibility.');
        }
    }
}
