<?php

namespace App\Http\Controllers\Admin;

use App\Models\AdminActivityLog;
use Illuminate\Http\Request;

class AdminAuditLogController extends AdminController
{
    public function index(Request $request)
    {
        $query = AdminActivityLog::with('admin:id,name,email')->latest();

        foreach (['admin_id', 'action', 'target_type'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        return response()->json(
            $query->paginate($this->perPage($request))->withQueryString()
        );
    }
}
