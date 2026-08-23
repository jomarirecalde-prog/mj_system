@php
    $isEdit = $isEdit ?? false;
    $schedule = $schedule ?? null;
    $defaultTimes = $defaultTimes ?? [
        'time_in' => '08:00',
        'time_out' => '17:00',
        'break_start' => '12:00',
        'break_end' => '13:00',
    ];

    $initialMode = old('schedule_mode');
    if ($initialMode === null) {
        if ($isEdit && $schedule) {
            $initialMode = 'keep';
        } else {
            $initialMode = 'default';
        }
    }

    $timeIn = old('time_in', $schedule ? substr((string) $schedule->time_in, 0, 5) : $defaultTimes['time_in']);
    $timeOut = old('time_out', $schedule ? substr((string) $schedule->time_out, 0, 5) : $defaultTimes['time_out']);
    $breakStart = old('break_start', $schedule && $schedule->break_start ? substr((string) $schedule->break_start, 0, 5) : $defaultTimes['break_start']);
    $breakEnd = old('break_end', $schedule && $schedule->break_end ? substr((string) $schedule->break_end, 0, 5) : $defaultTimes['break_end']);
    $initialShiftId = old('shift_id', $schedule?->shift_id);
@endphp

<div class="form-group" style="grid-column:1/-1;">
    <label class="form-label" for="schedule_mode">Work schedule / shift</label>
    <select name="schedule_mode" id="schedule_mode" class="form-select">
        @if($isEdit && $schedule)
            <option value="keep" @selected($initialMode === 'keep')>Keep current schedule ({{ $schedule->scheduleLabel() }})</option>
        @endif
        <option value="default" @selected($initialMode === 'default')>
            Use default ({{ $defaultTimes['time_in'] }} – {{ $defaultTimes['time_out'] }})
        </option>
        <option value="custom" @selected($initialMode === 'custom')>Custom schedule</option>
        @foreach($shifts as $shift)
            <option
                value="shift-{{ $shift->id }}"
                @selected($initialMode === 'shift-'.$shift->id)
                data-time-in="{{ substr($shift->time_in, 0, 5) }}"
                data-time-out="{{ substr($shift->time_out, 0, 5) }}"
                data-break-start="{{ $shift->break_start ? substr($shift->break_start, 0, 5) : '' }}"
                data-break-end="{{ $shift->break_end ? substr($shift->break_end, 0, 5) : '' }}"
            >
                {{ $shift->name }} ({{ substr($shift->time_in, 0, 5) }} – {{ substr($shift->time_out, 0, 5) }})
            </option>
        @endforeach
    </select>
    <p class="form-hint">Pick a shift template as a starting point, or choose Custom schedule to set your own times.</p>
</div>

<div id="schedule-custom-fields" class="form-grid" style="grid-column:1/-1; display:none;">
    <input type="hidden" name="shift_id" id="shift_id" value="{{ $initialShiftId }}">

    <div class="form-group">
        <label class="form-label" for="time_in">Time in</label>
        <input type="time" name="time_in" id="time_in" class="form-control" value="{{ $timeIn }}">
        @error('time_in')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="time_out">Time out</label>
        <input type="time" name="time_out" id="time_out" class="form-control" value="{{ $timeOut }}">
        @error('time_out')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="break_start">Break start</label>
        <input type="time" name="break_start" id="break_start" class="form-control" value="{{ $breakStart }}">
        @error('break_start')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="break_end">Break end</label>
        <input type="time" name="break_end" id="break_end" class="form-control" value="{{ $breakEnd }}">
        @error('break_end')<p class="form-error">{{ $message }}</p>@enderror
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    const modeSelect = document.getElementById('schedule_mode');
    const panel = document.getElementById('schedule-custom-fields');
    if (!modeSelect || !panel) {
        return;
    }

    const shiftInput = document.getElementById('shift_id');
    const timeIn = document.getElementById('time_in');
    const timeOut = document.getElementById('time_out');
    const breakStart = document.getElementById('break_start');
    const breakEnd = document.getElementById('break_end');

    function applyTemplate(option) {
        if (!option || !option.dataset.timeIn) {
            return;
        }
        timeIn.value = option.dataset.timeIn;
        timeOut.value = option.dataset.timeOut;
        if (option.dataset.breakStart) {
            breakStart.value = option.dataset.breakStart;
        }
        if (option.dataset.breakEnd) {
            breakEnd.value = option.dataset.breakEnd;
        }
    }

    function syncScheduleFields() {
        const mode = modeSelect.value;
        const showCustom = mode === 'custom' || mode.startsWith('shift-');

        panel.style.display = showCustom ? 'grid' : 'none';
        timeIn.required = showCustom;
        timeOut.required = showCustom;

        if (mode.startsWith('shift-')) {
            shiftInput.value = mode.slice(6);
            applyTemplate(modeSelect.selectedOptions[0]);
        } else {
            shiftInput.value = '';
        }
    }

    modeSelect.addEventListener('change', syncScheduleFields);
    syncScheduleFields();
})();
</script>
@endpush
@endonce
