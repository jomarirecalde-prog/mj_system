<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 50)->nullable()->after('position');
            }
            if (! Schema::hasColumn('users', 'date_hired')) {
                $table->date('date_hired')->nullable()->after('phone');
            }
        });

        Schema::create('attendance_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('attendance_record_id')->nullable()->constrained('attendance_records')->nullOnDelete();
            $table->date('attendance_date');
            $table->string('issue_type'); // missing_time_in, missing_time_out, incorrect_time_in, incorrect_time_out, other
            $table->dateTime('requested_time_in')->nullable();
            $table->dateTime('requested_time_out')->nullable();
            $table->text('reason');
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('admin_remarks')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        $exists = DB::table('system_settings')->where('key', 'employee_editable_fields')->exists();
        if (! $exists) {
            DB::table('system_settings')->insert([
                'key' => 'employee_editable_fields',
                'value' => json_encode(['phone', 'profile_picture']),
                'group' => 'employee_portal',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_correction_requests');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'date_hired')) {
                $table->dropColumn('date_hired');
            }
            if (Schema::hasColumn('users', 'phone')) {
                $table->dropColumn('phone');
            }
        });

        DB::table('system_settings')->where('key', 'employee_editable_fields')->delete();
    }
};
