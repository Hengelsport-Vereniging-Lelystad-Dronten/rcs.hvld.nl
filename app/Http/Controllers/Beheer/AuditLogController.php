<?php

namespace App\Http\Controllers\Beheer;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    /**
     * Toont de audit log pagina met filtermogelijkheden.
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $query = Activity::with('causer', 'subject') // Eager load relaties voor performance
            ->latest(); // Sorteer op nieuwste eerst

        // Filter op datum (vanaf/tot)
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter op gebruiker (causer)
        if ($request->filled('user_id')) {
            $query->where('causer_type', 'App\Models\User')->where('causer_id', $request->user_id);
        }

        // Filter op event type (created, updated, etc.)
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        // Filter op resource ID (subject_id)
        if ($request->filled('resource_id')) {
            $query->where('subject_id', $request->resource_id);
        }

        return Inertia::render('Beheer/AuditLog/Index', [
            'logs' => $query->paginate(50)->withQueryString(),
            'filters' => $request->only(['date_from', 'date_to', 'user_id', 'event', 'resource_id']),
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }
}