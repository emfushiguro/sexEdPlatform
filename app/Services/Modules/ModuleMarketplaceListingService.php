<?php

namespace App\Services\Modules;

use App\Models\Module;
use App\Models\ModuleMarketplaceListing;
use App\Models\User;
use App\Notifications\Instructor\ModuleMarketplaceStatusNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ModuleMarketplaceListingService
{
    public function __construct(
        private readonly ModuleCapacityService $capacity,
    ) {}

    public function upsert(Module $module, User $actor, array $data): ModuleMarketplaceListing
    {
        $action = (string) ($data['action'] ?? 'save');
        $currency = strtoupper((string) ($data['currency'] ?? ''));
        $price = (float) ($data['price_amount'] ?? 0);
        $seatCapacity = (int) ($data['seat_capacity'] ?? 0);
        $additionalPackages = $data['seat_packages'] ?? $module->marketplaceListing?->seat_packages ?? [];
        Validator::make(['seat_packages' => $additionalPackages], [
            'seat_packages' => ['array', 'max:9'],
            'seat_packages.*' => ['array:seat_capacity,price_amount'],
            'seat_packages.*.seat_capacity' => ['required', 'integer', 'min:1', 'distinct'],
            'seat_packages.*.price_amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
        ])->validate();
        if (collect($additionalPackages)->contains(fn ($package) => (int) $package['seat_capacity'] === $seatCapacity)) {
            throw ValidationException::withMessages(['seat_packages' => 'Each package must have a different seat quantity, including the default package.']);
        }
        $additionalPackages = array_map(fn ($package) => [
            'seat_capacity' => (int) $package['seat_capacity'],
            'price_amount' => number_format((float) $package['price_amount'], 2, '.', ''),
        ], $additionalPackages);

        if ($price <= 0 || $seatCapacity < 1 || $currency !== strtoupper((string) config('billing.currency.code'))) {
            throw ValidationException::withMessages([
                'listing' => 'The marketplace price, currency, and seat capacity are invalid.',
            ]);
        }

        if ($action === 'publish' && ! $module->isLearnerVisible()) {
            throw ValidationException::withMessages([
                'listing' => 'Only approved and published modules can be listed.',
            ]);
        }

        return DB::transaction(function () use ($module, $actor, $action, $currency, $price, $seatCapacity, $additionalPackages): ModuleMarketplaceListing {
            $lockedModule = $this->capacity->lock($module);
            if ($action === 'publish') {
                $this->capacity->assertAvailable($lockedModule, max([$seatCapacity, ...array_column($additionalPackages, 'seat_capacity')]));
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
                'seat_packages' => $additionalPackages,
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
            } elseif (! $listing->exists || ! $listing->isPublished()) {
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
