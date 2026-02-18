<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $query = AuditLog::query()
            ->with('actor:id,name,username,role');

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', $request->input('auditable_type'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('actor', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from') . ' 00:00:00');
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to') . ' 23:59:59');
        }

        $logs = $query
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/audit-logs/index', [
            'logs' => $logs,
            'filters' => [
                'action' => $request->input('action', ''),
                'auditable_type' => $request->input('auditable_type', ''),
                'search' => $request->input('search', ''),
                'date_from' => $request->input('date_from', ''),
                'date_to' => $request->input('date_to', ''),
            ],
        ]);
    }
}
