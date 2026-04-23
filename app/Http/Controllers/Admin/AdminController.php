<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

abstract class AdminController extends Controller
{
    protected function perPage(Request $request, int $default = 15, int $max = 100): int
    {
        return max(1, min((int) $request->input('per_page', $default), $max));
    }

    protected function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    protected function logActivity(
        Request $request,
        string $action,
        Model $target,
        ?string $reason = null,
        array $metadata = []
    ): AdminActivityLog {
        return AdminActivityLog::create([
            'admin_id' => $request->user()->id,
            'target_type' => $target->getTable(),
            'target_id' => $target->getKey(),
            'action' => $action,
            'reason' => $reason,
            'metadata' => empty($metadata) ? null : $metadata,
        ]);
    }
}
