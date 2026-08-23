<?php

namespace App\Http\Controllers;

use App\Models\AttendanceShift;
use App\Models\Department;
use App\Models\EmployeeSchedule;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function __construct(
        protected AttendanceService $attendance,
        protected AuditService $audit,
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeRoles(['admin', 'staff']);

        $employees = User::query()
            ->with(['activeQrCode', 'activeSchedule.shift'])
            ->whereNotNull('employee_id')
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('full_name', 'like', $term)
                        ->orWhere('first_name', 'like', $term)
                        ->orWhere('middle_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('name', 'like', $term)
                        ->orWhere('employee_id', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('department', 'like', $term)
                        ->orWhere('position', 'like', $term);
                });
            })
            ->when($request->filled('department'), fn ($q) => $q->where('department', $request->department))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('full_name')
            ->orderBy('name')
            ->paginate(30);

        $departments = User::query()
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        return view('employees.index', compact('employees', 'departments'));
    }

    public function create(): View
    {
        $this->authorizeRoles(['admin']);

        $departments = Department::query()->where('is_active', true)->orderBy('name')->pluck('name');
        $shifts = AttendanceShift::query()->where('is_active', true)->orderBy('name')->get();
        $suggestedId = $this->suggestEmployeeId();

        return view('employees.create', compact('departments', 'shifts', 'suggestedId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeRoles(['admin']);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'department' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'date_hired' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'allow_login' => ['nullable', 'boolean'],
            'role' => ['nullable', Rule::in(['admin', 'staff', 'viewer', 'employee'])],
            'shift_id' => ['nullable', 'exists:attendance_shifts,id'],
            'generate_qr' => ['nullable', 'boolean'],
        ]);

        $allowLogin = $request->boolean('allow_login');
        $firstName = trim($data['first_name']);
        $middleName = isset($data['middle_name']) ? trim($data['middle_name']) : null;
        $middleName = $middleName !== '' ? $middleName : null;
        $lastName = trim($data['last_name']);
        $fullName = User::composeFullName($firstName, $middleName, $lastName);
        $employeeId = $this->suggestEmployeeId();

        $email = $allowLogin && ! empty($data['email'])
            ? $data['email']
            : $this->makeInternalEmail($employeeId);

        $password = $allowLogin && ! empty($data['password'])
            ? $data['password']
            : Str::password(16);

        $role = $allowLogin
            ? ($data['role'] ?? 'employee')
            : 'employee';

        $employee = DB::transaction(function () use ($data, $firstName, $middleName, $lastName, $fullName, $employeeId, $email, $password, $role, $request) {
            $employee = User::query()->create([
                'employee_id' => $employeeId,
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'full_name' => $fullName,
                'name' => $this->makeUsername($fullName, $employeeId),
                'email' => $email,
                'department' => $data['department'],
                'position' => $data['position'] ?? null,
                'phone' => $data['phone'] ?? null,
                'date_hired' => $data['date_hired'] ?? null,
                'status' => $data['status'],
                'role' => $role,
                'password' => Hash::make($password),
            ]);

            if ($request->boolean('generate_qr', true)) {
                $this->attendance->generateQrCode($employee, $request->user());
            }

            if (! empty($data['shift_id'])) {
                $this->assignShift($employee, (int) $data['shift_id']);
            }

            return $employee;
        });

        $this->audit->log('create_employee', 'employees', User::class, $employee->id, null, [
            'employee_id' => $employee->employee_id,
            'full_name' => $employee->full_name,
            'department' => $employee->department,
            'position' => $employee->position,
            'status' => $employee->status,
        ]);

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Employee added successfully.'.($request->boolean('generate_qr', true) ? ' QR code generated.' : ''));
    }

    public function show(User $employee): View
    {
        $this->authorizeRoles(['admin', 'staff']);
        $this->assertEmployee($employee);

        $employee->load(['activeQrCode', 'activeSchedule.shift', 'qrCodes' => fn ($q) => $q->latest()]);

        return view('employees.show', compact('employee'));
    }

    public function edit(User $employee): View
    {
        $this->authorizeRoles(['admin']);
        $this->assertEmployee($employee);

        $departments = Department::query()->where('is_active', true)->orderBy('name')->pluck('name');
        $shifts = AttendanceShift::query()->where('is_active', true)->orderBy('name')->get();
        $employee->load('activeSchedule');

        return view('employees.edit', compact('employee', 'departments', 'shifts'));
    }

    public function update(Request $request, User $employee): RedirectResponse
    {
        $this->authorizeRoles(['admin']);
        $this->assertEmployee($employee);

        $data = $request->validate([
            'employee_id' => ['required', 'string', 'max:50', Rule::unique('users', 'employee_id')->ignore($employee->id)],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'department' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'date_hired' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($employee->id)],
            'role' => ['required', Rule::in(['admin', 'staff', 'viewer', 'employee'])],
            'shift_id' => ['nullable', 'exists:attendance_shifts,id'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $firstName = trim($data['first_name']);
        $middleName = isset($data['middle_name']) ? trim($data['middle_name']) : null;
        $middleName = $middleName !== '' ? $middleName : null;
        $lastName = trim($data['last_name']);
        $fullName = User::composeFullName($firstName, $middleName, $lastName);

        $previous = $employee->only(['employee_id', 'first_name', 'middle_name', 'last_name', 'full_name', 'department', 'position', 'phone', 'date_hired', 'status', 'email', 'role']);

        $payload = [
            'employee_id' => strtoupper(trim($data['employee_id'])),
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'full_name' => $fullName,
            'department' => $data['department'],
            'position' => $data['position'] ?? null,
            'phone' => $data['phone'] ?? null,
            'date_hired' => $data['date_hired'] ?? null,
            'status' => $data['status'],
            'email' => $data['email'],
            'role' => $data['role'],
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $employee->update($payload);

        if ($request->filled('shift_id')) {
            $this->assignShift($employee, (int) $data['shift_id']);

            if ($employee->isEmployee()) {
                app(\App\Services\NotificationService::class)->notify(
                    $employee->id,
                    'schedule_assigned',
                    'New schedule assigned',
                    'A new work schedule has been assigned to your account.',
                    route('employee.schedule')
                );
            }
        }

        $this->audit->log('update_employee', 'employees', User::class, $employee->id, $previous, $employee->fresh()->only(array_keys($previous)));

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Employee updated.');
    }

    public function destroy(User $employee): RedirectResponse
    {
        $this->authorizeRoles(['admin']);
        $this->assertEmployee($employee);

        if ($employee->id === auth()->id()) {
            return back()->with('error', 'You cannot remove your own employee record.');
        }

        // Soft approach: deactivate instead of hard delete to preserve attendance history
        $employee->update(['status' => 'inactive']);

        $this->audit->log('deactivate_employee', 'employees', User::class, $employee->id, null, [
            'employee_id' => $employee->employee_id,
            'status' => 'inactive',
        ]);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee deactivated. Attendance history was preserved.');
    }

    protected function assertEmployee(User $employee): void
    {
        if (empty($employee->employee_id)) {
            abort(404, 'Employee record not found.');
        }
    }

    protected function suggestEmployeeId(): string
    {
        $prefix = 'EMP-';
        $pattern = '/^'.preg_quote($prefix, '/').'(\d+)$/';

        $maxSequence = User::query()
            ->whereNotNull('employee_id')
            ->where('employee_id', 'like', $prefix.'%')
            ->pluck('employee_id')
            ->map(fn (string $id) => preg_match($pattern, $id, $matches) ? (int) $matches[1] : 0)
            ->max() ?? 0;

        $sequence = $maxSequence + 1;

        do {
            $candidate = sprintf('EMP-%04d', $sequence);
            $sequence++;
        } while (User::query()->where('employee_id', $candidate)->exists());

        return $candidate;
    }

    protected function makeUsername(string $fullName, string $employeeId): string
    {
        $base = Str::slug($fullName, '.');
        if ($base === '') {
            $base = Str::slug($employeeId, '.');
        }

        $candidate = $base;
        $i = 1;
        while (User::query()->where('name', $candidate)->exists()) {
            $candidate = $base.'.'.$i;
            $i++;
        }

        return $candidate;
    }

    protected function makeInternalEmail(string $employeeId): string
    {
        $local = Str::lower(preg_replace('/[^A-Za-z0-9]+/', '', $employeeId) ?: 'employee');
        $email = $local.'@employees.local';
        $i = 1;

        while (User::query()->where('email', $email)->exists()) {
            $email = $local.$i.'@employees.local';
            $i++;
        }

        return $email;
    }

    protected function assignShift(User $employee, int $shiftId): void
    {
        $shift = AttendanceShift::query()->findOrFail($shiftId);

        EmployeeSchedule::query()
            ->where('user_id', $employee->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        EmployeeSchedule::query()->create([
            'user_id' => $employee->id,
            'shift_id' => $shift->id,
            'schedule_type' => 'shift',
            'time_in' => $shift->time_in,
            'time_out' => $shift->time_out,
            'break_start' => $shift->break_start,
            'break_end' => $shift->break_end,
            'work_days' => [1, 2, 3, 4, 5],
            'rest_days' => [0, 6],
            'is_active' => true,
        ]);
    }
}
