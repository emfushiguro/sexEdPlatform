<?php

namespace App\Services\Modules;

use App\Models\Module;
use App\Models\ModuleMarketplaceListing;
use App\Models\User;
use App\Notifications\Instructor\ModuleMarketplaceStatusNotification;
use App\Services\Instructor\InstructorPlanCapabilityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ModuleMarketplaceListingService
{
    public function __construct(
        private readonly ModuleCapacityService $capacity,
        private readonly InstructorPlanCapabilityService $instructorPlans,
    ) {
    }

    public function upsert(Module $module, User $actor, array $data): ModuleMarketplaceListing
    {
        $action = (string) ($data['action'] ?? 'save');
        $currency = strtoupper((string) ($data['currency'] ?? ''));
        $price = (float) ($data['price_amount'] ?? 0);
        $seatCapacity = (int) ($data['seat_capacity'] ?? 0);

        if ($price <= 0 || $seatCapacity < 1 || $currency !== strtoupper((string) config('billing.currency.code'))) {
            throw ValidationException::withMessages([
                'listing' => 'The marketplace price, currency, and seat capacity are invalid.',
            ]);
        }

        $module->loadMissing('creator');
        if ($action === 'publish' && !$module->isLearnerVisible()) {
            throw ValidationException::withMessages([
                'listing' => 'Only approved and published modules can be listed.',
            ]);
        }

        if ($module->content_owner_type === 'instructor') {
            if (!$module->creator
                || !$this->instructorPlans->canPublishPaidModules($module->creator)
                || !$this->instructorPlans->canReceivePaidEnrollments($module->creator)) {
                throw ValidationException::withMessages([
                    'listing' => 'This instructor plan cannot distribute paid marketplace modules.',
                ]);
            }
        }

        return DB::transaction(function () use ($module, $actor, $action, $currency, $price, $seatCapacity): ModuleMarketplaceListing {
            $lockedModule = $this->capacity->lock($module);
            if ($action === 'publish') {
                $this->capacity->assertAvailable($lockedModule, $seatCapacity);
            }

            $listing = ModuleMarketplaceListing::query()->firstOrNew([
                'module_id' => $module->id,
            ]);
            $listing->fill([
                'seller_type' => $module->content_owner_type === 'admin' ? 'platform' : 'instructor',
                'seller_user_id' => $module->content_owner_type === 'admin' ? null : $module->created_by,
                'price_amount' => $price,
                'currency' => $currency,
                'seat_capacity' => $seatCapacity,
            ]);

            if ($action === 'publish') {
                $listing->fill([
                    'status' => ModuleMarketplaceListing::STATUS_PUBLISHED,
                    'published_by' => $actor->id,
                    'published_at' => now(),
                    'unpublished_by' => null,
                    'unpublished_at' => null,
                    'suspended_by' => null,
                    'suspended_at' => null,
                    'suspension_reason' => null,
                ]);
            } elseif (!$listing->exists || !$listing->isPublished()) {
                $listing->status = ModuleMarketplaceListing::STATUS_DRAFT;
            }

            $listing->save();

            return $listing->fresh();
        });
    }

    public function unpublish(ModuleMarketplaceListing $listing, User $actor): ModuleMarketplaceListing
    {
        $listing->forceFill([
            'status' => ModuleMarketplaceListing::STATUS_UNPUBLISHED,
            'unpublished_by' => $actor->id,
            'unpublished_at' => now(),
        ])->save();

        return $listing->fresh();
    }

    public function suspend(ModuleMarketplaceListing $listing, User $admin, string $reason): ModuleMarketplaceListing
    {
        return DB::transaction(function () use ($listing, $admin, $reason): ModuleMarketplaceListing {
            $listing->loadMissing('module');
            $listing->forceFill([
                'status' => ModuleMarketplaceListing::STATUS_SUSPENDED,
                'suspended_by' => $admin->id,
                'suspended_at' => now(),
                'suspension_reason' => trim($reason),
            ])->save();

            if ($listing->seller_type === 'instructor' && $listing->seller_user_id) {
                $sellerId = (int) $listing->seller_user_id;
                $listingId = (int) $listing->id;
                $reason = trim($reason);
                DB::afterCommit(function () use ($listingId, $sellerId, $reason): void {
                    $resolved = ModuleMarketplaceListing::query()->find($listingId);
                    $seller = User::query()->find($sellerId);
                    if ($resolved && $seller) {
                        $seller->notify(new ModuleMarketplaceStatusNotification($resolved, ModuleMarketplaceListing::STATUS_SUSPENDED, $reason));
                    }
                });
            }

            return $listing->fresh();
        });
    }
}
