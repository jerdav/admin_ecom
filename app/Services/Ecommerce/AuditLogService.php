<?php

namespace App\Services\Ecommerce;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogService
{
    /**
     * @param array<string, mixed>|null $before
     * @param array<string, mixed>|null $after
     * @param array<string, mixed>|null $meta
     */
    public function log(
        string $action,
        ?string $entityType = null,
        int|string|null $entityId = null,
        ?array $before = null,
        ?array $after = null,
        ?User $actor = null,
        ?array $meta = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'user_id' => $actor?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId !== null ? (int) $entityId : null,
            'before' => $before,
            'after' => $after,
            'meta' => $meta,
        ]);
    }
}
