<?php

namespace App\Services\Admin;

use App\Models\SuspensionAppeal;
use App\Models\UserSuspension;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ModerationSuspensionDashboardService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{stats: array<string, int>, suspensions: LengthAwarePaginator}
     */
    public function buildIndexPayload(array $filters): array
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $role = (string) ($filters['role'] ?? '');
        $severity = (string) ($filters['severity'] ?? '');
        $trigger = (string) ($filters['trigger'] ?? '');
        $status = (string) ($filters['status'] ?? '');
        $appealStatus = (string) ($filters['appeal_status'] ?? '');
        $sort = (string) ($filters['sort'] ?? 'latest');
        $perPage = max(5, min(100, (int) ($filters['per_page'] ?? 15)));

        $query = UserSuspension::query()
            ->with([
                'user:id,name,email,role',
                'enforcementAction:id,severity_level,trigger_type,action_type',
                'moderationCase:id,case_reference_code',
                'appeals:id,user_suspension_id,status,submitted_at',
            ])
            ->when($role !== '', function ($builder) use ($role): void {
                $builder->whereHas('user', fn ($userQuery) => $userQuery->where('role', $role));
            })
            ->when($severity !== '', function ($builder) use ($severity): void {
                $builder->whereHas('enforcementAction', fn ($actionQuery) => $actionQuery->where('severity_level', $severity));
            })
            ->when($trigger !== '', function ($builder) use ($trigger): void {
                $builder->whereHas('enforcementAction', fn ($actionQuery) => $actionQuery->where('trigger_type', $trigger));
            })
            ->when($status !== '', fn ($builder) => $builder->where('status', $status))
            ->when($appealStatus !== '', fn ($builder) => $builder->where('appeal_status', $appealStatus))
            ->when($search !== '', function ($builder) use ($search): void {
                $builder->where(function ($nested) use ($search): void {
                    $nested->where('id', 'like', '%' . $search . '%')
                        ->orWhereHas('user', function ($userQuery) use ($search): void {
                            $userQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('moderationCase', fn ($caseQuery) => $caseQuery->where('case_reference_code', 'like', '%' . $search . '%'));
                });
            });

        match ($sort) {
            'oldest' => $query->oldest('created_at'),
            'ending_soon' => $query->orderByRaw('CASE WHEN ends_at IS NULL THEN 1 ELSE 0 END')->orderBy('ends_at'),
            default => $query->latest('id'),
        };

        $suspensions = $query->paginate($perPage)->withQueryString();

        return [
            'stats' => [
                'total' => UserSuspension::query()->count(),
                'active' => UserSuspension::query()->where('status', 'active')->count(),
                'appeals_pending' => UserSuspension::query()->where('appeal_status', 'appeal_pending')->count(),
                'permanent' => UserSuspension::query()->where('status', 'active')->whereNull('ends_at')->count(),
            ],
            'suspensions' => $suspensions,
        ];
    }

    /**
     * @return array{suspension: UserSuspension, appeals: \Illuminate\Database\Eloquent\Collection<int, SuspensionAppeal>}
     */
    public function buildShowPayload(UserSuspension $suspension): array
    {
        $suspension->loadMissing([
            'user:id,name,email,role',
            'enforcementAction:id,user_id,moderation_case_id,severity_level,trigger_type,action_type,starts_at,ends_at,notes',
            'moderationCase:id,case_reference_code,status,decision,notes',
            'createdByAdmin:id,name',
            'revokedByAdmin:id,name',
        ]);

        $appeals = SuspensionAppeal::query()
            ->where('user_suspension_id', $suspension->id)
            ->with([
                'reviewedByAdmin:id,name',
                'threadMessages:id,suspension_appeal_id,sender_id,sender_role,message,created_at',
            ])
            ->latest('id')
            ->get();

        return [
            'suspension' => $suspension,
            'appeals' => $appeals,
        ];
    }
}
