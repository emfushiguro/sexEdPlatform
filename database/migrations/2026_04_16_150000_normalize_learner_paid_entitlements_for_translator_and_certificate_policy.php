<?php

use App\Models\FeatureCatalog;
use App\Models\PlanFeatureEntitlement;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $approvedPremiumKeys = [
            'unlimited_quiz_shields',
            'unlimited_username_change',
            'text_translator',
            'voice_speech_translator',
        ];

        $certificateKeys = [
            'downloadable_certificates',
            'certificate_pdf_download_access',
            'certificate_pdf_download',
            'certificates',
        ];

        $this->upsertLearnerFeatureCatalogRows();

        $activeLearnerPaidPlans = SubscriptionPlan::query()
            ->where('plan_audience', 'learner')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('price', '>', 0)
                    ->orWhereHas('planPrices', function ($priceQuery) {
                        $priceQuery->where('is_active', true)
                            ->where('amount_minor', '>', 0);
                    });
            })
            ->get(['id']);

        if ($activeLearnerPaidPlans->isEmpty()) {
            return;
        }

        $approvedFeatureIds = FeatureCatalog::query()
            ->whereIn('key', $approvedPremiumKeys)
            ->pluck('id', 'key');

        $certificateFeatureIds = FeatureCatalog::query()
            ->whereIn('key', $certificateKeys)
            ->pluck('id');

        $learnerFeatureIds = FeatureCatalog::query()
            ->where('category', 'learner')
            ->pluck('id');

        DB::transaction(function () use ($activeLearnerPaidPlans, $approvedPremiumKeys, $approvedFeatureIds, $certificateFeatureIds, $learnerFeatureIds) {
            foreach ($activeLearnerPaidPlans as $plan) {
                $this->disableLearnerEntitlementsForPlan($plan->id, $learnerFeatureIds);
                $this->enableApprovedPremiumEntitlementsForPlan($plan->id, $approvedPremiumKeys, $approvedFeatureIds);
                $this->disableCertificateEntitlementsForPlan($plan->id, $certificateFeatureIds);

                $plan->update([
                    'features' => $approvedPremiumKeys,
                ]);
            }
        });
    }

    public function down(): void
    {
        $translatorKeys = [
            'text_translator',
            'voice_speech_translator',
        ];

        $certificateKeys = [
            'downloadable_certificates',
            'certificate_pdf_download_access',
        ];

        $activeLearnerPaidPlans = SubscriptionPlan::query()
            ->where('plan_audience', 'learner')
            ->where('is_active', true)
            ->get(['id']);

        if ($activeLearnerPaidPlans->isEmpty()) {
            return;
        }

        $translatorFeatureIds = FeatureCatalog::query()
            ->whereIn('key', $translatorKeys)
            ->pluck('id');

        $certificateFeatureIds = FeatureCatalog::query()
            ->whereIn('key', $certificateKeys)
            ->pluck('id');

        DB::transaction(function () use ($activeLearnerPaidPlans, $translatorFeatureIds, $certificateFeatureIds) {
            foreach ($activeLearnerPaidPlans as $plan) {
                if ($translatorFeatureIds->isNotEmpty()) {
                    PlanFeatureEntitlement::query()
                        ->where('plan_id', $plan->id)
                        ->whereIn('feature_id', $translatorFeatureIds)
                        ->update([
                            'is_enabled' => false,
                            'is_unlimited' => false,
                            'quota_value' => null,
                        ]);
                }

                if ($certificateFeatureIds->isNotEmpty()) {
                    PlanFeatureEntitlement::query()
                        ->where('plan_id', $plan->id)
                        ->whereIn('feature_id', $certificateFeatureIds)
                        ->update([
                            'is_enabled' => true,
                            'is_unlimited' => false,
                            'quota_value' => null,
                        ]);
                }

                $plan->update([
                    'features' => [
                        'unlimited_quiz_shields',
                        'unlimited_username_change',
                        'downloadable_certificates',
                    ],
                ]);
            }
        });
    }

    private function upsertLearnerFeatureCatalogRows(): void
    {
        $features = [
            [
                'key' => 'unlimited_username_change',
                'name' => 'Unlimited Username Changes',
                'description' => 'Allow learners to change username any time without cooldown',
                'value_type' => 'boolean',
                'unit_label' => null,
                'category' => 'learner',
                'is_active' => true,
            ],
            [
                'key' => 'unlimited_quiz_shields',
                'name' => 'Unlimited Quiz Shields',
                'description' => 'Remove daily limit on quiz shields and retry protection',
                'value_type' => 'boolean',
                'unit_label' => null,
                'category' => 'learner',
                'is_active' => true,
            ],
            [
                'key' => 'text_translator',
                'name' => 'Text Translator',
                'description' => 'Unlock page and lesson text translation tools',
                'value_type' => 'boolean',
                'unit_label' => null,
                'category' => 'learner',
                'is_active' => true,
            ],
            [
                'key' => 'voice_speech_translator',
                'name' => 'Voice Speech Translator',
                'description' => 'Unlock translated text-to-speech lesson narration',
                'value_type' => 'boolean',
                'unit_label' => null,
                'category' => 'learner',
                'is_active' => true,
            ],
        ];

        foreach ($features as $featureData) {
            FeatureCatalog::query()->updateOrCreate(
                ['key' => $featureData['key']],
                $featureData
            );
        }
    }

    private function disableLearnerEntitlementsForPlan(int $planId, Collection $learnerFeatureIds): void
    {
        if ($learnerFeatureIds->isEmpty()) {
            return;
        }

        PlanFeatureEntitlement::query()
            ->where('plan_id', $planId)
            ->whereIn('feature_id', $learnerFeatureIds)
            ->update([
                'is_enabled' => false,
                'is_unlimited' => false,
                'quota_value' => null,
            ]);
    }

    private function enableApprovedPremiumEntitlementsForPlan(int $planId, array $approvedPremiumKeys, Collection $approvedFeatureIds): void
    {
        foreach ($approvedPremiumKeys as $featureKey) {
            $featureId = $approvedFeatureIds->get($featureKey);
            if (!$featureId) {
                continue;
            }

            PlanFeatureEntitlement::query()->updateOrCreate(
                [
                    'plan_id' => $planId,
                    'feature_id' => (int) $featureId,
                ],
                [
                    'is_enabled' => true,
                    'is_unlimited' => true,
                    'quota_value' => null,
                ]
            );
        }
    }

    private function disableCertificateEntitlementsForPlan(int $planId, Collection $certificateFeatureIds): void
    {
        if ($certificateFeatureIds->isEmpty()) {
            return;
        }

        PlanFeatureEntitlement::query()
            ->where('plan_id', $planId)
            ->whereIn('feature_id', $certificateFeatureIds)
            ->update([
                'is_enabled' => false,
                'is_unlimited' => false,
                'quota_value' => null,
            ]);
    }
};
