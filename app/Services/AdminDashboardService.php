<?php

namespace App\Services;

use App\Models\InstructorApplication;
use App\Models\Module;
use App\Models\ModuleReviewRequest;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Collection;

class AdminDashboardService
{
    public function getCommandCenterPayload(): array
    {
        return [
            'snapshot_metrics' => [
                [
                    'label' => 'Total Users',
                    'value' => User::query()->count(),
                    'description' => 'All accounts currently present in the platform.',
                    'accent' => 'sky',
                ],
                [
                    'label' => 'Total Instructors',
                    'value' => User::role('instructor')->count(),
                    'description' => 'Approved instructors with publishing privileges.',
                    'accent' => 'violet',
                ],
                [
                    'label' => 'Total Learners',
                    'value' => User::role('learner')->count(),
                    'description' => 'Learner accounts currently enrolled in the platform.',
                    'accent' => 'emerald',
                ],
                [
                    'label' => 'Total Modules',
                    'value' => Module::query()->count(),
                    'description' => 'All learning modules across instructor and admin ownership.',
                    'accent' => 'amber',
                ],
                [
                    'label' => 'Active Subscriptions',
                    'value' => Subscription::query()->where('status', 'active')->count(),
                    'description' => 'Subscribers with active billing and access entitlement.',
                    'accent' => 'fuchsia',
                ],
                [
                    'label' => 'Pending Instructor Applications',
                    'value' => InstructorApplication::query()->where('status', 'pending')->count(),
                    'description' => 'Learner applications waiting for instructor approval.',
                    'accent' => 'orange',
                ],
                [
                    'label' => 'Pending Module Reviews',
                    'value' => ModuleReviewRequest::query()->where('status', 'in_review')->count(),
                    'description' => 'Submitted modules currently queued for moderation.',
                    'accent' => 'indigo',
                ],
                [
                    'label' => 'Payments Needing Review',
                    'value' => Payment::query()->whereIn('status', ['pending', 'processing'])->count(),
                    'description' => 'Transactions pending reconciliation or completion.',
                    'accent' => 'rose',
                ],
            ],
            'moderation_queues' => [
                [
                    'label' => 'Instructor Applications',
                    'count' => InstructorApplication::query()->where('status', 'pending')->count(),
                    'cta_label' => 'Open Queue',
                    'cta_route' => route('admin.instructor-applications.index', ['status' => 'pending']),
                    'description' => 'Review learner requests to become instructors.',
                    'accent' => 'amber',
                ],
                [
                    'label' => 'Module Published Review',
                    'count' => ModuleReviewRequest::query()->where('status', 'in_review')->count(),
                    'cta_label' => 'Open Queue',
                    'cta_route' => route('admin.content-reviews.index'),
                    'description' => 'Moderate instructor module submissions before publish.',
                    'accent' => 'sky',
                ],
            ],
            'recent_activity' => $this->getRecentSystemActivity(),
        ];
    }

    private function getRecentSystemActivity(): Collection
    {
        $applicationEvents = InstructorApplication::query()
            ->with('user')
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(function (InstructorApplication $application): array {
                $status = (string) $application->status;
                $tone = $status === 'approved' ? 'emerald' : ($status === 'rejected' ? 'rose' : 'amber');

                return [
                    'type' => 'Instructor Application',
                    'title' => ($application->user?->name ?? 'Learner') . ' application is ' . str_replace('_', ' ', $status),
                    'meta' => 'Application #' . $application->id,
                    'occurred_at' => $application->updated_at,
                    'href' => route('admin.instructor-applications.index', [
                        'status' => $status,
                        'focus' => $application->id,
                    ]),
                    'tone' => $tone,
                ];
            })
            ->all();

        $moduleReviewEvents = ModuleReviewRequest::query()
            ->with('module')
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(function (ModuleReviewRequest $reviewRequest): array {
                $status = (string) $reviewRequest->status;
                $tone = $status === 'approved' ? 'emerald' : ($status === 'rejected' ? 'rose' : 'sky');

                return [
                    'type' => 'Module Review',
                    'title' => ($reviewRequest->module_title ?: 'Module') . ' review is ' . str_replace('_', ' ', $status),
                    'meta' => 'Review #' . $reviewRequest->id,
                    'occurred_at' => $reviewRequest->updated_at,
                    'href' => route('admin.content-reviews.show', $reviewRequest),
                    'tone' => $tone,
                ];
            })
            ->all();

        $paymentEvents = Payment::query()
            ->with('user')
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(function (Payment $payment): array {
                $status = is_object($payment->status) ? $payment->status->value : (string) $payment->status;
                $tone = $status === 'completed' ? 'emerald' : ($status === 'failed' ? 'rose' : 'amber');

                return [
                    'type' => 'Payment',
                    'title' => 'Payment #' . $payment->id . ' is ' . str_replace('_', ' ', $status),
                    'meta' => $payment->user?->name ?? 'Unknown user',
                    'occurred_at' => $payment->updated_at,
                    'href' => route('admin.payments.show', $payment),
                    'tone' => $tone,
                ];
            })
            ->all();

        return collect($applicationEvents)
            ->concat($moduleReviewEvents)
            ->concat($paymentEvents)
            ->sortByDesc(fn (array $event) => $event['occurred_at'])
            ->take(12)
            ->values();
    }
}
