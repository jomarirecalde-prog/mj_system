@extends('layouts.employee')

@section('title', 'Attendance Calendar')

@section('content')
<div class="page-header">
    <div>
        <h1>Attendance Calendar</h1>
        <p class="page-header__meta">{{ \Carbon\Carbon::parse($month.'-01')->format('F Y') }}</p>
    </div>
    <form method="get">
        <input type="month" name="month" class="form-control" value="{{ $month }}" onchange="this.form.submit()">
    </form>
</div>

<div class="card mb-2">
    <div class="card__body">
        <div class="emp-cal-legend">
            <span><i class="emp-cal-dot present"></i> Present</span>
            <span><i class="emp-cal-dot late"></i> Late</span>
            <span><i class="emp-cal-dot absent"></i> Absent</span>
            <span><i class="emp-cal-dot on_leave"></i> Leave</span>
            <span><i class="emp-cal-dot rest_day"></i> Rest Day</span>
            <span><i class="emp-cal-dot incomplete"></i> Incomplete</span>
        </div>
        <div class="emp-cal">
            <div class="emp-cal__head"><span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span></div>
            <div class="emp-cal__grid">
                @for($i = 0; $i < $start->dayOfWeek; $i++)
                    <div class="emp-cal__cell is-empty"></div>
                @endfor
                @foreach($days as $day)
                    <button type="button" class="emp-cal__cell status-{{ $day['status'] }}" data-date="{{ $day['date'] }}" title="{{ $day['label'] }}">
                        <span class="emp-cal__day">{{ $day['day'] }}</span>
                        <span class="emp-cal__status">{{ $day['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="card" id="day-detail" hidden>
    <div class="card__header"><h2 class="card__title" id="day-detail-title">Day details</h2></div>
    <div class="card__body">
        <dl class="dl-grid" id="day-detail-body"></dl>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const detail = document.getElementById('day-detail');
    const title = document.getElementById('day-detail-title');
    const body = document.getElementById('day-detail-body');
    const url = @json(route('employee.calendar.day'));
    document.querySelectorAll('.emp-cal__cell[data-date]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const date = btn.getAttribute('data-date');
            const res = await fetch(url + '?date=' + encodeURIComponent(date), { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            if (!json.success) return;
            title.textContent = json.date;
            body.innerHTML = `
                <div class="dl-item"><dt>Schedule</dt><dd>${json.schedule}</dd></div>
                <div class="dl-item"><dt>Time In</dt><dd>${json.time_in}</dd></div>
                <div class="dl-item"><dt>Time Out</dt><dd>${json.time_out}</dd></div>
                <div class="dl-item"><dt>Total Hours</dt><dd>${json.hours}</dd></div>
                <div class="dl-item"><dt>Late</dt><dd>${json.late}</dd></div>
                <div class="dl-item"><dt>Undertime</dt><dd>${json.undertime}</dd></div>
                <div class="dl-item"><dt>Overtime</dt><dd>${json.overtime}</dd></div>
                <div class="dl-item"><dt>Status</dt><dd>${json.status}</dd></div>
                <div class="dl-item"><dt>Remarks</dt><dd>${json.remarks}</dd></div>
            `;
            detail.hidden = false;
            detail.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    });
})();
</script>
@endpush
