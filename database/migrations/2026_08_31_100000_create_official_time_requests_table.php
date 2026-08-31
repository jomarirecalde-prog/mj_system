<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('official_time_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('request_type')->default('temporary');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();

            $table->time('current_time_in');
            $table->time('current_time_out');
            $table->time('current_break_start')->nullable();
            $table->time('current_break_end')->nullable();
            $table->string('current_schedule_type')->nullable();

            $table->time('requested_time_in');
            $table->time('requested_time_out');
            $table->time('requested_break_start')->nullable();
            $table->time('requested_break_end')->nullable();

            $table->text('reason');
            $table->text('notes')->nullable();
            $table->string('status')->default('pending');

            $table->text('admin_remarks')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('employee_schedule_id')->nullable()->constrained('employee_schedules')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_time_requests');
    }
};
