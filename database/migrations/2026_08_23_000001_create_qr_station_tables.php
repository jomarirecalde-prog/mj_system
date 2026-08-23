<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_stations', function (Blueprint $table) {
            $table->id();
            $table->string('station_name');
            $table->string('station_code')->unique();
            $table->string('password');
            $table->string('location');
            $table->text('description')->nullable();
            $table->string('building')->nullable();
            $table->string('department')->nullable();
            $table->string('floor_area')->nullable();
            $table->string('timezone')->default('Asia/Manila');
            $table->string('status')->default('active'); // active, inactive
            $table->foreignId('authorized_device_id')->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('last_activity_at');
        });

        Schema::create('qr_station_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained('qr_stations')->cascadeOnDelete();
            $table->string('device_identifier')->unique();
            $table->string('device_token_hash');
            $table->string('device_name')->nullable();
            $table->string('browser')->nullable();
            $table->string('operating_system')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('status')->default('authorized'); // authorized, revoked
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['station_id', 'status']);
        });

        Schema::table('qr_stations', function (Blueprint $table) {
            $table->foreign('authorized_device_id')
                ->references('id')
                ->on('qr_station_devices')
                ->nullOnDelete();
        });

        Schema::create('qr_station_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained('qr_stations')->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('qr_station_devices')->nullOnDelete();
            $table->string('action');
            $table->text('description')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['station_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('qr_stations', function (Blueprint $table) {
            $table->dropForeign(['authorized_device_id']);
        });

        Schema::dropIfExists('qr_station_activity_logs');
        Schema::dropIfExists('qr_station_devices');
        Schema::dropIfExists('qr_stations');
    }
};
