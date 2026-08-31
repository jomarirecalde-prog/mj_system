<?php

namespace App\Services;

use App\Models\EmployeeSchedule;
use App\Models\OfficialTimeRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OfficialTimeRequestService
{
    public function __construct(
        protected AttendanceService $attendance,
        protected AuditService $audit,
        protected NotificationService $notifications,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function currentScheduleSnapshot(User $user): array
    {
        $today = now('Asia/Manila')->toDateString();
        $resolved = $this->attendance->resolveSchedule($user, $today);
        $schedule = $user->activeSchedule;

        return [
            'time_in' => $resolved['time_in'],
            'time_out' => $resolved['time_out'],
            'break_start' => $resolved['break_start'],
            'break_end' => $resolved['break_end'],
            'schedule_type' => $schedule?->schedule_type ?? 'regular',
            'shift_name' => $resolved['shift_name'],
            'work_days' => $resolved['work_days'] ?? [1, 2, 3, 4, 5],
            'rest_days' => $resolved['rest_days'] ?? [0, 6],
            'is_active' => true,
        ];
    }

    /**
     * @return list<string>
     */
    public function detectConflicts(User $user, string $from, ?string $to, ?int $excludeRequestId = null): array
    {
        $conflicts = [];
        $end = $to ?? '9999-12-31';

        $pendingRequests = OfficialTimeRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->when($excludeRequestId, fn ($q) => $q->where('id', '!=', $excludeRequestId))
            ->get();

        foreach ($pendingRequests as $request) {
            if ($this->periodsOverlap($from, $end, $request->effective_from->toDateString(), $request->effective_to?->toDateString())) {
                $conflicts[] = 'Pending official time request #'.$request->id.' ('.$request->effectivePeriodLabel().')';
            }
        }

        $approvedRequests = OfficialTimeRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereNotNull('employee_schedule_id')
            ->when($excludeRequestId, fn ($q) => $q->where('id', '!=', $excludeRequestId))
            ->get();

        foreach ($approvedRequests as $request) {
            if ($this->periodsOverlap($from, $end, $request->effective_from->toDateString(), $request->effective_to?->toDateString())) {
                $conflicts[] = 'Approved official time request #'.$request->id.' ('.$request->effectivePeriodLabel().')';
            }
        }

        $schedules = EmployeeSchedule::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        foreach ($schedules as $schedule) {
            if ($schedule->effective_from === null && $schedule->effective_to === null) {
                continue;
            }

            $scheduleFrom = $schedule->effective_from?->toDateString() ?? '1900-01-01';
            $scheduleTo = $schedule->effective_to?->toDateString() ?? '9999-12-31';

            if ($this->periodsOverlap($from, $end, $scheduleFrom, $scheduleTo === '9999-12-31' ? null : $scheduleTo)) {
                $conflicts[] = 'Active schedule #'.$schedule->id.' ('.$schedule->scheduleLabel().', '.$this->formatSchedulePeriod($schedule).')';
            }
        }

        return array_values(array_unique($conflicts));
    }

    public function approve(OfficialTimeRequest $request, User $reviewer, ?string $adminRemarks, Request $httpRequest): void
    {
        if (! $request->isPending()) {
            throw new \RuntimeException('This request has already been reviewed.');
        }

        $conflicts = $this->detectConflicts(
            $request->user,
            $request->effective_from->toDateString(),
            $request->effective_to?->toDateString(),
            $request->id
        );

        if ($conflicts !== []) {
            throw new \RuntimeException('Schedule conflict detected. '.implode(' ', $conflicts));
        }

        DB::transaction(function () use ($request, $reviewer, $adminRemarks, $httpRequest) {
            $previous = $request->toArray();
            $schedule = $this->applyApprovedSchedule($request);

            $request->update([
                'status' => 'approved',
                'admin_remarks' => $adminRemarks,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now('Asia/Manila'),
                'employee_schedule_id' => $schedule->id,
            ]);

            $this->audit->log(
                'approve_official_time_request',
                'attendance',
                OfficialTimeRequest::class,
                $request->id,
                $previous,
                $request->fresh()?->toArray()
            );

            $this->audit->log(
                'create_schedule',
                'attendance',
                EmployeeSchedule::class,
                $schedule->id,
                null,
                $schedule->toArray()
            );
        });

        $period = $request->effectivePeriodLabel();
        $this->notifications->notify(
            $request->user_id,
            'official_time_approved',
            'Official Time Request Approved',
            'Your Official Time Request ('.$period.') has been approved.',
            route('employee.official-time.show', $request)
        );
    }

    public function reject(OfficialTimeRequest $request, User $reviewer, string $rejectionReason): void
    {
        if (! $request->isPending()) {
            throw new \RuntimeException('This request has already been reviewed.');
        }

        $previous = $request->toArray();

        $request->update([
            'status' => 'rejected',
            'admin_remarks' => $rejectionReason,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now('Asia/Manila'),
        ]);

        $this->audit->log(
            'reject_official_time_request',
            'attendance',
            OfficialTimeRequest::class,
            $request->id,
            $previous,
            $request->fresh()?->toArray()
        );

        $period = $request->effectivePeriodLabel();
        $this->notifications->notify(
            $request->user_id,
            'official_time_rejected',
            'Official Time Request Rejected',
            'Your Official Time Request ('.$period.') was rejected. Reason: '.$rejectionReason,
            route('employee.official-time.show', $request)
        );
    }

    public function cancel(OfficialTimeRequest $request, User $employee): void
    {
        if (! $request->isPending()) {
            throw new \RuntimeException('Only pending requests can be cancelled.');
        }

        if ($request->user_id !== $employee->id) {
            throw new \RuntimeException('Unauthorized.');
        }

        $previous = $request->toArray();

        $request->update([
            'status' => 'cancelled',
            'cancelled_at' => now('Asia/Manila'),
        ]);

        $this->audit->log(
            'cancel_official_time_request',
            'attendance',
            OfficialTimeRequest::class,
            $request->id,
            $previous,
            $request->fresh()?->toArray()
        );
    }

    protected function applyApprovedSchedule(OfficialTimeRequest $request): EmployeeSchedule
    {
        $user = $request->user;
        $resolved = $this->attendance->resolveSchedule($user, $request->effective_from->toDateString());

        if ($request->request_type === 'permanent' || $request->effective_to === null) {
            $this->closeOverlappingSchedules($user, $request->effective_from);
        }

        return EmployeeSchedule::query()->create([
            'user_id' => $user->id,
            'shift_id' => null,
            'schedule_type' => $request->current_schedule_type ?? 'regular',
            'time_in' => $this->normalizeTime($request->requested_time_in),
            'time_out' => $this->normalizeTime($request->requested_time_out),
            'break_start' => $request->requested_break_start ? $this->normalizeTime($request->requested_break_start) : null,
            'break_end' => $request->requested_break_end ? $this->normalizeTime($request->requested_break_end) : null,
            'work_days' => $resolved['work_days'] ?? [1, 2, 3, 4, 5],
            'rest_days' => $resolved['rest_days'] ?? [0, 6],
            'effective_from' => $request->effective_from,
            'effective_to' => $request->effective_to,
            'is_active' => true,
            'notes' => 'Approved official time request #'.$request->id.': '.$request->reason,
        ]);
    }

    protected function closeOverlappingSchedules(User $user, Carbon $effectiveFrom): void
    {
        $dayBefore = $effectiveFrom->copy()->subDay()->toDateString();

        EmployeeSchedule::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->where(function ($q) use ($effectiveFrom) {
                $q->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $effectiveFrom);
            })
            ->where(function ($q) use ($effectiveFrom) {
                $q->whereNull('effective_from')
                    ->orWhereDate('effective_from', '<=', $effectiveFrom);
            })
            ->each(function (EmployeeSchedule $schedule) use ($dayBefore, $effectiveFrom) {
                if ($schedule->effective_from && $schedule->effective_from->gt($effectiveFrom)) {
                    return;
                }

                $newEnd = $dayBefore;
                if ($schedule->effective_to && $schedule->effective_to->lt($effectiveFrom)) {
                    return;
                }

                $schedule->update(['effective_to' => $newEnd]);
            });
    }

    protected function periodsOverlap(string $fromA, string $toA, string $fromB, ?string $toB): bool
    {
        $endA = $toA === '9999-12-31' ? $toA : $toA;
        $endB = $toB ?? '9999-12-31';

        return $fromA <= $endB && $fromB <= $endA;
    }

    protected function formatSchedulePeriod(EmployeeSchedule $schedule): string
    {
        $from = $schedule->effective_from?->format('M j, Y') ?? 'Open';
        $to = $schedule->effective_to?->format('M j, Y') ?? 'No end date';

        return $from.' – '.$to;
    }

    protected function normalizeTime(mixed $value): string
    {
        $time = substr((string) $value, 0, 5);

        return strlen($time) === 5 ? $time.':00' : (string) $value;
    }

    public static function pendingCountForUser(int $userId): int
    {
        return OfficialTimeRequest::query()
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->count();
    }

    public static function pendingCount(): int
    {
        return OfficialTimeRequest::query()
            ->where('status', 'pending')
            ->count();
    }
}
