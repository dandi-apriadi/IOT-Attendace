<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(): View
    {
        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->latest('created_at')
            ->paginate(50);

        $summary = AuditLog::query()
            ->selectRaw('COUNT(*) AS total_events')
            ->selectRaw("SUM(CASE WHEN action = 'login' THEN 1 ELSE 0 END) AS auth_events")
            ->selectRaw("SUM(CASE WHEN action = 'login_failed' THEN 1 ELSE 0 END) AS warning_events")
            ->selectRaw("SUM(CASE WHEN action LIKE '%failed%' THEN 1 ELSE 0 END) AS error_events")
            ->first();

        return view('reports.audit', [
            'logs' => $logs,
            'summary' => $summary,
        ]);
    }
}
