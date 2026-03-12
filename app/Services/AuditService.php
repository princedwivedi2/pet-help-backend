<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditService
{
    /**
     * Log a status change for any model.
     */
    public function logStatusChange(
        ?int $userId,
        string $modelType,
        int $modelId,
        string $oldStatus,
        string $newStatus,
        ?string $description = null
    ): AuditLog {
        return AuditLog::log(
            $userId,
            $modelType,
            $modelId,
            'status_changed',
            ['status' => $oldStatus],
            ['status' => $newStatus],
            $description ?? "Status changed from {$oldStatus} to {$newStatus}"
        );
    }

    /**
     * Log any action on a model.
     */
    public function log(
        ?int $userId,
        string $modelType,
        int $modelId,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null
    ): AuditLog {
        return AuditLog::log($userId, $modelType, $modelId, $action, $oldValues, $newValues, $description);
    }

    /**
     * Get audit trail for a model.
     */
    public function getAuditTrail(string $modelType, int $modelId, int $limit = 50): array
    {
        return AuditLog::forModel($modelType, $modelId)
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
