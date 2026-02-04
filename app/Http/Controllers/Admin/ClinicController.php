<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\City;
use App\Http\Requests\StoreClinicRequest;
use App\Http\Requests\UpdateClinicRequest;
use App\Services\ClinicApprovalService;
use App\Services\ClinicCacheService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

use function view;

class ClinicController extends Controller
{
    protected $approvalService;
    protected $cacheService;

    public function __construct(ClinicApprovalService $approvalService, ClinicCacheService $cacheService)
    {
        $this->approvalService = $approvalService;
        $this->cacheService = $cacheService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Clinic::class);
        
        $query = Clinic::query()->with('user', 'approvedBy');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('approval_status', $request->status);
        }

        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('barangay', 'like', "%{$search}%");
            });
        }

        // Filter by active status
        if ($request->filled('active')) {
            $query->where('is_active', (bool) $request->active);
        }

        $clinics = $query->orderBy('created_at', 'desc')->paginate(15);

        // Use static Cavite city list for dropdown
        $allCities = \App\Models\City::caviteCities()->pluck('name')->toArray();
        $types = $this->cacheService->getClinicTypes();
        $stats = $this->cacheService->getApprovalStats();

        return view('admin.clinics.index', compact('clinics', 'allCities', 'types', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $clinic = new Clinic();
        // Get Cavite cities/municipalities
        $cities = City::caviteCities();
        return view('admin.clinics.create', compact('clinic', 'cities'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClinicRequest $request): RedirectResponse
    {
        $data = $request->validated();
        // Set user_id to current authenticated user if not provided
        if (empty($data['user_id'])) {
            $data['user_id'] = Auth::id();
        }
        
        $clinic = Clinic::create($data);

        if ($request->boolean('auto_approve')) {
            $clinic->update([
                'approval_status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => \Carbon\Carbon::now(),
            ]);
        }

        // Clear cache after creating clinic
        $this->cacheService->clearStatsCache();

        return redirect()
            ->route('admin.clinics.show', $clinic)
            ->with('success', 'Clinic created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Clinic $clinic): View
    {
        $clinic->load('user', 'approvedBy');
        return view('admin.clinics.show', compact('clinic'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Clinic $clinic): View
    {
        // Get Cavite cities/municipalities
        $cities = City::caviteCities();
        return view('admin.clinics.edit', compact('clinic', 'cities'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClinicRequest $request, Clinic $clinic): RedirectResponse
    {
        $clinic->update($request->validated());

        // Clear cache after updating clinic
        $this->cacheService->clearStatsCache();

        return redirect()
            ->route('admin.clinics.show', $clinic)
            ->with('success', 'Clinic updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Clinic $clinic): RedirectResponse
    {
        $clinic->delete();

        // Clear cache after deleting clinic
        $this->cacheService->clearStatsCache();

        return redirect()
            ->route('admin.clinics.index')
            ->with('success', 'Clinic deleted successfully.');
    }

    /**
     * Approve a clinic.
     */
    public function approve(Clinic $clinic): RedirectResponse
    {
        $clinic->update([
            'approval_status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => \Carbon\Carbon::now(),
            'rejection_reason' => null,
        ]);

        // Clear cache after approval status change
        $this->cacheService->clearStatsCache();

        return redirect()
            ->back()
            ->with('success', 'Clinic approved successfully.');
    }

    /**
     * Reject a clinic.
     */
    public function reject(Request $request, Clinic $clinic): RedirectResponse
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $clinic->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        // Clear cache after rejection status change
        $this->cacheService->clearStatsCache();

        return redirect()
            ->back()
            ->with('success', 'Clinic rejected successfully.');
    }

    /**
     * Toggle active status.
     */
    public function toggleActive(Clinic $clinic): RedirectResponse
    {
        $clinic->update(['is_active' => !$clinic->is_active]);

        // Clear cache after active status change
        $this->cacheService->clearStatsCache();

        $status = $clinic->is_active ? 'activated' : 'deactivated';
        return redirect()
            ->back()
            ->with('success', "Clinic {$status} successfully.");
    }

    /**
     * Toggle verified status.
     */
    public function toggleVerified(Clinic $clinic): RedirectResponse
    {
        $clinic->update(['verified' => !$clinic->verified]);

        // Clear cache after verified status change
        $this->cacheService->clearStatsCache();

        $status = $clinic->verified ? 'verified' : 'unverified';
        return redirect()
            ->back()
            ->with('success', "Clinic {$status} successfully.");
    }

    /**
     * Get clinic analytics for dashboard.
     */
    public function analytics(): View
    {
        $stats = [
            'total' => Clinic::count(),
            'approved' => Clinic::approved()->count(),
            'pending' => Clinic::pending()->count(),
            'rejected' => Clinic::where('approval_status', 'rejected')->count(),
            'active' => Clinic::active()->count(),
            'verified' => Clinic::verified()->count(),
        ];

        $clinicsByCity = Clinic::selectRaw('city, COUNT(*) as count')
            ->whereNotNull('city')
            ->groupBy('city')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'city');

        $clinicsByType = Clinic::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type');

        $recentClinics = Clinic::with('user')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('admin.clinics.analytics', compact('stats', 'clinicsByCity', 'clinicsByType', 'recentClinics'));
    }

    /**
     * Bulk approve clinics.
     */
    public function bulkApprove(Request $request): RedirectResponse
    {
        Gate::authorize('bulkActions', Clinic::class);
        
        $request->validate([
            'clinic_ids' => 'required|array',
            'clinic_ids.*' => 'exists:clinics,id',
        ]);

        $results = $this->approvalService->bulkApprove($request->clinic_ids, Auth::user());
        
        $successCount = count($results['success']);
        $failedCount = count($results['failed']);
        
        $message = "{$successCount} clinic(s) approved successfully.";
        if ($failedCount > 0) {
            $message .= " {$failedCount} clinic(s) failed to approve.";
        }
        
        $this->cacheService->clearStatsCache();
        
        return redirect()->back()->with('success', $message);
    }

    /**
     * Bulk reject clinics.
     */
    public function bulkReject(Request $request): RedirectResponse
    {
        $request->validate([
            'clinic_ids' => 'required|array',
            'clinic_ids.*' => 'exists:clinics,id',
            'rejection_reason' => 'required|string|max:1000',
        ]);

        Clinic::whereIn('id', $request->clinic_ids)
            ->update([
                'approval_status' => 'rejected',
                'rejection_reason' => $request->rejection_reason,
                'approved_by' => null,
                'approved_at' => null,
            ]);

        // Clear cache after bulk rejection
        $this->cacheService->clearStatsCache();

        $count = count($request->clinic_ids);
        return redirect()
            ->back()
            ->with('success', "{$count} clinics rejected successfully.");
    }
}
