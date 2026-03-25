<?php

namespace App\Services\Admin;

use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\DB;

class PlanLifecycleService
{
    public function impactSnapshot(SubscriptionPlan $plan): array
    {
        return [
            'total_subscribers' => $plan->subscriptions()->count(),
            'active_subscribers' => $plan->subscriptions()->where('status', 'active')->count(),
        ];
    }

    public function activate(SubscriptionPlan $plan): SubscriptionPlan
    {
        if ($plan->isArchived()) {
            throw new \InvalidArgumentException('Archived plans must be restored before activation.');
        }

        $plan->update(['is_active' => true]);

        return $plan->refresh();
    }

    public function deactivate(SubscriptionPlan $plan): SubscriptionPlan
    {
        $plan->update(['is_active' => false]);

        return $plan->refresh();
    }

    public function archive(SubscriptionPlan $plan): SubscriptionPlan
    {
        DB::transaction(function () use ($plan): void {
            $plan->update([
                'is_active' => false,
                'archived_at' => now(),
            ]);
        });

        return $plan->refresh();
    }

    public function restore(SubscriptionPlan $plan): SubscriptionPlan
    {
        DB::transaction(function () use ($plan): void {
            $plan->update([
                'archived_at' => null,
                'is_active' => false,
            ]);
        });

        return $plan->refresh();
    }
}
