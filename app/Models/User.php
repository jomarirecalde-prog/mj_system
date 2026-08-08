<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements CanResetPasswordContract
{
    /** @use HasFactory<UserFactory> */
    use CanResetPassword, HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'name',
        'full_name',
        'email',
        'department',
        'position',
        'phone',
        'date_hired',
        'role',
        'status',
        'profile_picture',
        'password',
        'last_login_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'date_hired' => 'date',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }

    public function canAccessEmployeePortal(): bool
    {
        return $this->isEmployee() && $this->isActive() && ! empty($this->employee_id);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * @param  array<int, string>|string  $roles
     */
    public function hasRole(array|string $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];

        return in_array($this->role, $roles, true);
    }

    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }

    public function canModifyInventory(): bool
    {
        return $this->hasRole(['admin', 'staff']);
    }

    public function displayName(): string
    {
        return $this->full_name ?: $this->name;
    }

    public function canAccessAttendance(): bool
    {
        return $this->hasRole(['admin', 'staff']);
    }

    public function canManageAttendance(): bool
    {
        return $this->isAdmin();
    }

    public function canCorrectAttendance(): bool
    {
        return $this->isAdmin();
    }

    public function activeQrCode(): HasOne
    {
        return $this->hasOne(EmployeeQrCode::class)->ofMany(
            ['id' => 'max'],
            fn ($query) => $query->where('status', 'active')
        );
    }

    public function qrCodes(): HasMany
    {
        return $this->hasMany(EmployeeQrCode::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    public function activeSchedule(): HasOne
    {
        return $this->hasOne(EmployeeSchedule::class)->ofMany(
            ['id' => 'max'],
            fn ($query) => $query->where('is_active', true)
        );
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function leaveRecords(): HasMany
    {
        return $this->hasMany(LeaveRecord::class);
    }

    public function correctionRequests(): HasMany
    {
        return $this->hasMany(AttendanceCorrectionRequest::class);
    }

    /**
     * @return list<string>
     */
    public static function employeeEditableFields(): array
    {
        $raw = SystemSetting::get('employee_editable_fields', '["phone","profile_picture"]');

        if (is_array($raw)) {
            return array_values(array_filter($raw, 'is_string'));
        }

        $decoded = json_decode((string) $raw, true);

        return is_array($decoded)
            ? array_values(array_filter($decoded, 'is_string'))
            : ['phone', 'profile_picture'];
    }
}
