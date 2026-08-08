<?php

namespace App\Console\Commands;

use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkAttendanceAbsences extends Command
{
    protected $signature = 'attendance:mark-absences {date?}';

    protected $description = 'Mark employees absent for a work day when no attendance was recorded';

    public function handle(AttendanceService $attendance): int
    {
        $date = $this->argument('date') ?: now('Asia/Manila')->subDay()->toDateString();
        $date = Carbon::parse($date, 'Asia/Manila')->toDateString();

        $count = $attendance->markAbsencesForDate($date);
        $this->info("Marked/created {$count} attendance day record(s) for {$date}.");

        return self::SUCCESS;
    }
}
