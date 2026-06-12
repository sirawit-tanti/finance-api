<?php

namespace App\Services;

use App\Models\ActivityLog;

class ActivityLogger
{
    public static function log (
        ?int $userId,
        string $action,
        ?string $model = null,
        ?int $modelId = null,
        ?string $description = null,
        array $properties = []
    ): void {
        ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'model' => $model,
            'model_id' => $modelId,
            'description' => $description,
            'properties' => $properties,
        ]);
    }
}