<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    /** Audit trail for important actions (mostly admin). Never put secrets in $properties. */
    public static function log(string $action, ?Model $subject = null, array $properties = []): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'properties' => $properties ?: null,
            'ip_address' => request()?->ip(),
        ]);
    }
}
