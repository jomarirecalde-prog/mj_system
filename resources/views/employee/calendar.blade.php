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

@push('styles')
<style>
.emp-cal-legend { display:flex; flex-wrap:wrap; gap:.75rem 1.25rem; margin-bottom:1rem; font-size:.9rem; }
.emp-cal-dot { display:inline-block; width:.7rem; height:.7rem; border-radius:50%; margin-right:.35rem; vertical-align:middle; }
.emp-cal-dot.present { background:#16a34a; }
.emp-cal-dot.late { background:#d97706; }
.emp-cal-dot.absent { background:#dc2626; }
.emp-cal-dot.on_leave { background:#2563eb; }
.emp-cal-dot.rest_day { background:#94a3b8; }
.emp-cal-dot.incomplete { background:#7c3aed; }
.emp-cal__head, .emp-cal__grid { display:grid; grid-template-columns:repeat(7,1fr); gap:.35rem; }
.emp-cal__head { margin-bottom:.35rem; text-align:center; font-weight:600; color:var(--muted); font-size:.85rem; }
.emp-cal__cell { min-height:4.5rem; border:1px solid var(--border, #e2e8f0); border-radius:.5rem; background:#fff; padding:.4rem; text-align:left; cursor:pointer; }
.emp-cal__cell.is-empty { background:transparent; border-color:transparent; cursor:default; }
.emp-cal__day { display:block; font-weight:700; }
.emp-cal__status { display:block; font-size:.7rem; color:var(--muted); margin-top:.25rem; line-height:1.2; }
.emp-cal__cell.status-present { border-left:4px solid #16a34a; }
.emp-cal__cell.status-late { border-left:4px solid #d97706; }
.emp-cal__cell.status-absent { border-left:4px solid #dc2626; }
.emp-cal__cell.status-on_leave, .emp-cal__cell.status-official_business { border-left:4px solid #2563eb; }
.emp-cal__cell.status-rest_day { border-left:4px solid #94a3b8; }
.emp-cal__cell.status-incomplete, .emp-cal__cell.status-undertime { border-left:4px solid #7c3aed; }
</style>
@endpush

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
