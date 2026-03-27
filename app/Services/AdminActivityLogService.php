<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminActivityLogService
{
    public function log(
        string $action,
        string $entityType,
        int|string|null $entityId = null,
        ?array $before = null,
        ?array $after = null,
        ?array $meta = null,
        ?Request $request = null,
        ?int $adminUserId = null,
    ): AdminActivityLog {
        $request ??= request();
        $adminUserId ??= Auth::id();

        return AdminActivityLog::create([
            'admin_user_id' => $adminUserId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'before_json' => $before,
            'after_json' => $after,
            'meta_json' => $meta,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ]);
    }

    public function logModelMutation(
        string $action,
        Model $entity,
        ?array $before = null,
        ?array $after = null,
        ?array $meta = null,
        ?Request $request = null,
        ?int $adminUserId = null,
    ): AdminActivityLog {
        return $this->log(
            action: $action,
            entityType: $entity::class,
            entityId: $entity->getKey(),
            before: $before,
            after: $after,
            meta: $meta,
            request: $request,
            adminUserId: $adminUserId,
        );
    }
}
