<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\UpsertModuleMarketplaceListingRequest;
use App\Models\Module;
use App\Models\ModuleMarketplaceListing;
use App\Services\Modules\ModuleMarketplaceListingService;
use Illuminate\Http\Request;

class ModuleMarketplaceListingController extends Controller
{
    public function __construct(
        private readonly ModuleMarketplaceListingService $listings,
    ) {
    }

    public function update(UpsertModuleMarketplaceListingRequest $request, Module $module)
    {
        $this->authorize('manageMarketplaceListing', $module);

        try {
            $this->listings->upsert($module, $request->user(), $request->validated());
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        return back()->with('success', 'Connector marketplace listing saved.');
    }

    public function destroy(Request $request, Module $module)
    {
        $this->authorize('manageMarketplaceListing', $module);
        $listing = $module->marketplaceListing()->firstOrFail();
        $this->listings->unpublish($listing, $request->user());

        return back()->with('success', 'Connector marketplace listing unpublished.');
    }

    public function suspend(Request $request, ModuleMarketplaceListing $listing)
    {
        $listing->loadMissing('module');
        $this->authorize('suspendMarketplaceListing', $listing->module);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $this->listings->suspend($listing, $request->user(), $validated['reason']);

        return back()->with('success', 'Connector marketplace listing suspended.');
    }
}
