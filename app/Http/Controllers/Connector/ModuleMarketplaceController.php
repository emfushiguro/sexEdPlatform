<?php

namespace App\Http\Controllers\Connector;

use App\Http\Controllers\Controller;
use App\Models\Connector;
use App\Models\ModuleMarketplaceListing;
use App\Services\Connectors\ConnectorAccessService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleMarketplaceController extends Controller
{
    public function __construct(
        private readonly ConnectorAccessService $access,
    ) {
    }

    public function index(Request $request, Connector $connector): View
    {
        $this->access->abortUnlessWorkspace($request->user(), $connector);
        $canManage = $this->access->hasPermission($request->user(), $connector, 'connector.manage_modules');

        $listings = ModuleMarketplaceListing::query()
            ->with([
                'module.creator.instructorProfile',
                'module.creator.adminCreatorProfile',
                'module.publishedRevision',
            ])
            ->where('status', ModuleMarketplaceListing::STATUS_PUBLISHED)
            ->whereHas('module', function ($query): void {
                $query->where('is_published', true)
                    ->where(function ($visible): void {
                        $visible->whereNotNull('published_revision_id')
                            ->orWhereNull('current_review_status')
                            ->orWhere('current_review_status', 'approved');
                    });
            })
            ->latest('id')
            ->get();

        $listings->each(fn (ModuleMarketplaceListing $listing) => $listing->module->applyPublishedSnapshot());
        $entitlements = $connector->connectorModuleEntitlements()
            ->whereIn('module_id', $listings->pluck('module_id'))
            ->get()
            ->keyBy('module_id');

        return view('connectors.modules.marketplace.index', [
            'connector' => $connector,
            'listings' => $listings,
            'entitlements' => $entitlements,
            'canManage' => $canManage,
        ]);
    }

    public function show(Request $request, Connector $connector, ModuleMarketplaceListing $listing): View
    {
        $this->access->abortUnlessWorkspace($request->user(), $connector);
        $listing->load([
            'module.creator.instructorProfile',
            'module.creator.adminCreatorProfile',
            'module.publishedRevision',
            'module.lessons' => fn ($query) => $query
                ->select(['id', 'module_id', 'title', 'description', 'duration', 'order', 'is_published'])
                ->where('is_published', true)
                ->orderBy('order'),
        ]);

        abort_unless(
            $listing->status === ModuleMarketplaceListing::STATUS_PUBLISHED
                && $listing->module?->isLearnerVisible(),
            404,
        );

        $module = $listing->module->applyPublishedSnapshot();
        $entitlement = $connector->connectorModuleEntitlements()
            ->where('module_id', $module->id)
            ->latest('id')
            ->first();

        return view('connectors.modules.marketplace.show', [
            'connector' => $connector,
            'listing' => $listing,
            'module' => $module,
            'lessons' => $module->lessons,
            'entitlement' => $entitlement,
            'canManage' => $this->access->hasPermission($request->user(), $connector, 'connector.manage_modules'),
            'canOpenLessons' => false,
        ]);
    }
}
