<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'position')) {
                $table->string('position')->nullable()->after('department');
            }
        });

        Schema::create('attendance_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->time('time_in');
            $table->time('time_out');
            $table->time('break_start')->nullable();
            $table->time('break_end')->nullable();
            $table->unsignedSmallInteger('grace_period_minutes')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('employee_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('attendance_shifts')->nullOnDelete();
            $table->string('schedule_type')->default('regular'); // regular, shift
            $table->time('time_in')->default('08:00:00');
            $table->time('time_out')->default('17:00:00');
            $table->time('break_start')->nullable();
            $table->time('break_end')->nullable();
            $table->json('work_days')->nullable(); // [1,2,3,4,5] Mon-Fri
            $table->json('rest_days')->nullable(); // [0,6] Sun,Sat
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });

        Schema::create('employee_qr_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('status')->default('active'); // active, disabled, revoked
            $table->timestamp('generated_at')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disabled_at')->nullable();
            $table->foreignId('disabled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disable_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('holiday_date');
            $table->string('type')->default('regular'); // regular, special
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique('holiday_date');
        });

        Schema::create('leave_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('leave_type')->default('leave'); // leave, official_business, half_day
            $table->string('status')->default('approved'); // pending, approved, rejected
            $table->text('reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'start_date', 'end_date']);
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->time('schedule_time_in')->nullable();
            $table->time('schedule_time_out')->nullable();
            $table->string('shift_name')->nullable();
            $table->dateTime('time_in')->nullable();
            $table->dateTime('time_out')->nullable();
            $table->unsignedInteger('total_minutes')->nullable();
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('undertime_minutes')->default(0);
            $table->unsignedInteger('overtime_minutes')->default(0);
            $table->string('status')->default('incomplete'); // present, late, absent, on_leave, official_business, half_day, undertime, incomplete, rest_day
            $table->string('source')->default('qr'); // qr, manual, system
            $table->foreignId('time_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('time_out_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('time_in_device')->nullable();
            $table->string('time_out_device')->nullable();
            $table->string('time_in_location')->nullable();
            $table->string('time_out_location')->nullable();
            $table->text('remarks')->nullable();
            $table->boolean('is_corrected')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'attendance_date']);
            $table->index(['attendance_date', 'status']);
            $table->index('status');
        });

        Schema::create('attendance_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_record_id')->constrained('attendance_records')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('field_name'); // time_in, time_out, status, remarks
            $table->text('original_value')->nullable();
            $table->text('corrected_value')->nullable();
            $table->text('reason');
            $table->foreignId('corrected_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('corrected_at');
            $table->string('ip_address')->nullable();
            $table->string('device')->nullable();
            $table->timestamps();

            $table->index('attendance_record_id');
        });

        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('attendance_record_id')->nullable()->constrained('attendance_records')->nullOnDelete();
            $table->string('action');
            $table->text('original_value')->nullable();
            $table->text('new_value')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('logged_at');
            $table->string('ip_address')->nullable();
            $table->string('device')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['logged_at', 'action']);
        });

        Schema::create('attendance_qr_scan_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('qr_code')->nullable();
            $table->string('action')->nullable(); // time_in, time_out, rejected
            $table->date('scan_date')->nullable();
            $table->time('scan_time')->nullable();
            $table->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('device')->nullable();
            $table->string('result'); // success, late, already_in, already_out, invalid, inactive, cooldown
            $table->text('remarks')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->index(['scan_date', 'result']);
            $table->index('qr_code');
        });

        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->string('label')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');
        Schema::dropIfExists('attendance_qr_scan_logs');
        Schema::dropIfExists('attendance_logs');
        Schema::dropIfExists('attendance_adjustments');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('leave_records');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('employee_qr_codes');
        Schema::dropIfExists('employee_schedules');
        Schema::dropIfExists('attendance_shifts');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'position')) {
                $table->dropColumn('position');
            }
        });
    }
};
