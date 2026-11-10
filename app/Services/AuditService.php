<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Record an auditable event with current actor and IP address.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function log(string $action, Model $auditable, ?array $oldValues = null, ?array $newValues = null): AuditLog
    {
        $userId = Auth::id() ?? (Auth::guard('sanctum')->id() ?? null);
        $ipAddress = Request::ip() ?? '127.0.0.1';

        return AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => substr((string) $ipAddress, 0, 45),
            'created_at' => now(),
        ]);
    }
}
