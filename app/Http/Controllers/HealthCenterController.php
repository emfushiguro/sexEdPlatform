<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HealthCenterController extends Controller
{
    /**
     * Display the health centers map page.
     */
    public function index(): View
    {
        $allCities = \App\Models\City::caviteCities()->pluck('name')->toArray();
        // Count clinics with 24/7 operating hours (using multiple patterns for reliability)
        $open247Count = \App\Models\Clinic::where('approval_status', 'approved')
            ->where('is_active', true)
            ->where(function($query) {
                $query->where('operating_hours', 'like', '%24/7%')
                      ->orWhere('operating_hours', 'like', '%24-7%')
                      ->orWhere('operating_hours', 'like', '%24 hours%')
                      ->orWhereRaw("LOWER(operating_hours) LIKE '%24%7%'");
            })
            ->count();
        return view('health-centers.index', compact('allCities', 'open247Count'));
    }

    /**
     * Display a specific health center detail page.
     */
    public function show(Clinic $clinic): View
    {
        // Only show approved and active clinics to public
        abort_unless($clinic->isApproved() && $clinic->isActive(), 404);

        return view('health-centers.show', compact('clinic'));
    }
}
