<?php

namespace App\Http\Controllers;

use App\Models\OfficialTimeRequest;
use App\Services\AuditService;
use App\Services\OfficialTimeRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfficialTimeRequestController extends Controller
{
    public function __construct(
        protected OfficialTimeRequestService $service,
        protected AuditService $audit,
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeRoles(['admin']);

        $requests = OfficialTimeRequest::query()
            ->with(['user', 'reviewer'])
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->input('search'), function ($q, $search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('full_name', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%");
                });
            })
            ->when($request->input('department'), function ($q, $dept) {
                $q->whereHas('user', fn ($uq) => $uq->where('department', $dept));
            })
            ->when($request->input('date_from'), fn ($q, $d) => $q->whereDate('effective_from', '>=', $d))
            ->when($request->input('date_to'), fn ($q, $d) => $q->whereDate('effective_from', '<=', $d))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $stats = [
            'pending' => OfficialTimeRequest::query()->where('status', 'pending')->count(),
            'approved' => OfficialTimeRequest::query()->where('status', 'approved')->count(),
            'rejected' => OfficialTimeRequest::query()->where('status', 'rejected')->count(),
        ];

        $departments = OfficialTimeRequest::query()
            ->join('users', 'official_time_requests.user_id', '=', 'users.id')
            ->whereNotNull('users.department')
            ->where('users.department', '!=', '')
            ->distinct()
            ->orderBy('users.department')
            ->pluck('users.department');

        return view('attendance.official-time.index', compact('requests', 'stats', 'departments'));
    }

    public function show(OfficialTimeRequest $officialTimeRequest): View
    {
        $this->authorizeRoles(['admin']);

        $officialTimeRequest->load(['user', 'reviewer', 'employeeSchedule']);

        $conflicts = $officialTimeRequest->isPending()
            ? $officialTimeRequest->detectConflicts()
            : [];

        $auditLogs = \App\Models\AuditLog::query()
            ->where('item_type', OfficialTimeRequest::class)
            ->where('item_id', $officialTimeRequest->id)
            ->latest()
            ->limit(20)
            ->with('user')
            ->get();

        return view('attendance.official-time.show', compact('officialTimeRequest', 'conflicts', 'auditLogs'));
    }

    public function approve(Request $request, OfficialTimeRequest $officialTimeRequest): RedirectResponse
    {
        $this->authorizeRoles(['admin']);

        $data = $request->validate([
            'admin_remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->service->approve($officialTimeRequest, $request->user(), $data['admin_remarks'] ?? null, $request);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('attendance.official-time.show', $officialTimeRequest)
            ->with('success', 'Official Time Request approved and schedule applied.');
    }

    public function reject(Request $request, OfficialTimeRequest $officialTimeRequest): RedirectResponse
    {
        $this->authorizeRoles(['admin']);

        $data = $request->validate([
            'admin_remarks' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $this->service->reject($officialTimeRequest, $request->user(), $data['admin_remarks']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('attendance.official-time.show', $officialTimeRequest)
            ->with('success', 'Official Time Request rejected.');
    }
}
