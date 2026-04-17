<?php

use App\Support\SubscriptionFeatureKeys;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $planId = DB::table('subscription_plans')
            ->where('slug', 'instructor-free-plan')
            ->value('id');

        if (!$planId) {
            return;
        }

        $keys = [
            SubscriptionFeatureKeys::INSTRUCTOR_CAN_PUBLISH_PAID_MODULES,
            SubscriptionFeatureKeys::INSTRUCTOR_CAN_RECEIVE_PAID_ENROLLMENTS,
            SubscriptionFeatureKeys::INSTRUCTOR_CAN_VIEW_EARNINGS,
        ];

        $featureIds = DB::table('feature_catalog')
            ->whereIn('key', $keys)
            ->pluck('id');

        foreach ($featureIds as $featureId) {
            $exists = DB::table('plan_feature_entitlements')
                ->where('plan_id', $planId)
                ->where('feature_id', $featureId)
                ->exists();

            if ($exists) {
                DB::table('plan_feature_entitlements')
                    ->where('plan_id', $planId)
                    ->where('feature_id', $featureId)
                    ->update([
                        'is_enabled' => true,
                        'quota_value' => null,
                        'is_unlimited' => false,
                        'updated_at' => now(),
                    ]);

                continue;
            }

            DB::table('plan_feature_entitlements')->insert([
                'plan_id' => $planId,
                'feature_id' => $featureId,
                'is_enabled' => true,
                'quota_value' => null,
                'is_unlimited' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $planId = DB::table('subscription_plans')
            ->where('slug', 'instructor-free-plan')
            ->value('id');

        if (!$planId) {
            return;
        }

        $keys = [
            SubscriptionFeatureKeys::INSTRUCTOR_CAN_PUBLISH_PAID_MODULES,
            SubscriptionFeatureKeys::INSTRUCTOR_CAN_RECEIVE_PAID_ENROLLMENTS,
            SubscriptionFeatureKeys::INSTRUCTOR_CAN_VIEW_EARNINGS,
        ];

        $featureIds = DB::table('feature_catalog')
            ->whereIn('key', $keys)
            ->pluck('id');

        if ($featureIds->isEmpty()) {
            return;
        }

        DB::table('plan_feature_entitlements')
            ->where('plan_id', $planId)
            ->whereIn('feature_id', $featureIds)
            ->update([
                'is_enabled' => false,
                'updated_at' => now(),
            ]);
    }
};
