<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Models\Clinic;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClinicApprovalService
{
    public function approve(Clinic $clinic, User $approver, string $notes = null): bool
    {
        return DB::transaction(function () use ($clinic, $approver, $notes) {
            $clinic->update([
                'approval_status' => ApprovalStatus::APPROVED,
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'rejection_reason' => null, // Clear any previous rejection reason
                'notes' => $notes ? ($clinic->notes ? $clinic->notes . "\n\nApproval Notes: " . $notes : $notes) : $clinic->notes,
            ]);

            $this->logApprovalAction($clinic, $approver, 'approved', $notes);
            
            // Trigger event for notifications
            event(new \App\Events\ClinicApproved($clinic, $approver));
            
            return true;
        });
    }

    public function reject(Clinic $clinic, User $rejector, string $reason, string $notes = null): bool
    {
        if (empty($reason)) {
            throw new \InvalidArgumentException('Rejection reason is required');
        }

        return DB::transaction(function () use ($clinic, $rejector, $reason, $notes) {
            $clinic->update([
                'approval_status' => ApprovalStatus::REJECTED,
                'approved_by' => null,
                'approved_at' => null,
                'rejection_reason' => $reason,
                'notes' => $notes ? ($clinic->notes ? $clinic->notes . "\n\nRejection Notes: " . $notes : $notes) : $clinic->notes,
            ]);

            $this->logApprovalAction($clinic, $rejector, 'rejected', $reason, $notes);
            
            // Trigger event for notifications
            event(new \App\Events\ClinicRejected($clinic, $rejector, $reason));
            
            return true;
        });
    }

    public function resetToPending(Clinic $clinic, User $user, string $reason = null): bool
    {
        return DB::transaction(function () use ($clinic, $user, $reason) {
            $clinic->update([
                'approval_status' => ApprovalStatus::PENDING,
                'approved_by' => null,
                'approved_at' => null,
                'rejection_reason' => null,
            ]);

            $this->logApprovalAction($clinic, $user, 'reset_to_pending', $reason);
            
            return true;
        });
    }

    public function bulkApprove(array $clinicIds, User $approver, string $notes = null): array
    {
        $results = ['success' => [], 'failed' => []];
        
        foreach ($clinicIds as $clinicId) {
            try {
                $clinic = Clinic::findOrFail($clinicId);
                if ($this->approve($clinic, $approver, $notes)) {
                    $results['success'][] = $clinic->id;
                }
            } catch (\Exception $e) {
                $results['failed'][] = ['id' => $clinicId, 'error' => $e->getMessage()];
            }
        }
        
        return $results;
    }

    public function getApprovalStats(): array
    {
        return [
            'total' => Clinic::count(),
            'pending' => Clinic::where('approval_status', ApprovalStatus::PENDING)->count(),
            'approved' => Clinic::where('approval_status', ApprovalStatus::APPROVED)->count(),
            'rejected' => Clinic::where('approval_status', ApprovalStatus::REJECTED)->count(),
            'pending_this_week' => Clinic::where('approval_status', ApprovalStatus::PENDING)
                ->where('created_at', '>=', now()->startOfWeek())
                ->count(),
        ];
    }

    protected function logApprovalAction(Clinic $clinic, User $user, string $action, string $reason = null, string $notes = null): void
    {
        Log::channel('audit')->info('Clinic approval action', [
            'action' => $action,
            'clinic_id' => $clinic->id,
            'clinic_name' => $clinic->name,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'reason' => $reason,
            'notes' => $notes,
            'timestamp' => now()->toISOString(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
        ]);
    }
}