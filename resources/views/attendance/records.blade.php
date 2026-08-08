@extends('layouts.app')

@section('title', 'DTR Records')

@section('content')
<div class="page-header">
    <div>
        <h1>DTR Records</h1>
        <p class="page-header__meta">Complete daily time records with live search</p>
    </div>
    @if(auth()->user()->isAdmin())
        <a href="{{ route('attendance.corrections.create') }}" class="btn btn--secondary">DTR Correction</a>
    @endif
</div>

<div class="card mb-2">
    <div class="card__body">
        <form id="dtr-filters" class="filters" method="get">
            <div class="form-group"><label class="form-label">Search</label><input type="search" name="search" id="dtr-search" class="form-control" value="{{ request('search') }}" placeholder="Name or employee ID" autocomplete="off"></div>
            <div class="form-group"><label class="form-label">Employee</label>
                <select name="employee_id" class="form-select" id="dtr-employee">
                    <option value="">All</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}" @selected(request('employee_id')==$e->id)>{{ $e->displayName() }} ({{ $e->employee_id }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group"><label class="form-label">Department</label>
                <select name="department" class="form-select" id="dtr-dept">
                    <option value="">All</option>
                    @foreach($departments as $d)<option value="{{ $d }}" @selected(request('department')===$d)>{{ $d }}</option>@endforeach
                </select>
            </div>
            <div class="form-group"><label class="form-label">From</label><input type="date" name="date_from" id="dtr-from" class="form-control" value="{{ request('date_from') }}"></div>
            <div class="form-group"><label class="form-label">To</label><input type="date" name="date_to" id="dtr-to" class="form-control" value="{{ request('date_to') }}"></div>
            <div class="form-group"><label class="form-label">Status</label>
                <select name="status" class="form-select" id="dtr-status">
                    <option value="">All</option>
                    @foreach(['present','late','absent','on_leave','official_business','half_day','undertime','incomplete','rest_day'] as $s)
                        <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn--secondary">Filter</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card__body table-wrap">
        <table class="data-table" id="dtr-table">
            <thead>
            <tr>
                <th>Date</th><th>Employee ID</th><th>Employee Name</th><th>Department</th><th>Schedule</th>
                <th>Time In</th><th>Time Out</th><th>Total Hours</th><th>Late</th><th>Undertime</th><th>Overtime</th><th>Status</th><th>Remarks</th><th></th>
            </tr>
            </thead>
            <tbody id="dtr-tbody">
            @forelse($records as $r)
                <tr>
                    <td>{{ $r->attendance_date?->format('M d, Y') }}</td>
                    <td>{{ $r->user?->employee_id }}</td>
                    <td>{{ $r->user?->displayName() }}</td>
                    <td>{{ $r->user?->department ?? '—' }}</td>
                    <td>{{ $r->scheduleLabel() }}</td>
                    <td>{{ $r->time_in ? ph_datetime($r->time_in, 'h:i:s A') : '—' }}</td>
                    <td>{{ $r->time_out ? ph_datetime($r->time_out, 'h:i:s A') : '—' }}</td>
                    <td>{{ $r->totalHoursLabel() }}</td>
                    <td>{{ $r->minutesLabel($r->late_minutes) }}</td>
                    <td>{{ $r->minutesLabel($r->undertime_minutes) }}</td>
                    <td>{{ $r->minutesLabel($r->overtime_minutes) }}</td>
                    <td>{{ $r->statusLabel() }}</td>
                    <td>{{ $r->remarks ?: '—' }}</td>
                    <td><a class="btn btn--ghost btn--sm" href="{{ route('attendance.records.show', $r) }}">View</a></td>
                </tr>
            @empty
                <tr><td colspan="14" class="text-muted">No DTR records found.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div id="dtr-pagination">
            @include('partials.pagination', ['paginator' => $records->withQueryString()])
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const baseUrl = @json(route('attendance.records'));
    const tbody = document.getElementById('dtr-tbody');
    const pagination = document.getElementById('dtr-pagination');
    let timer = null;

    function esc(v) {
        return String(v ?? '—').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function params() {
        const fd = new FormData(document.getElementById('dtr-filters'));
        const q = new URLSearchParams(fd);
        q.set('ajax', '1');
        return q.toString();
    }

    async function liveSearch() {
        try {
            const res = await fetch(baseUrl + '?' + params(), { headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (!data.success) return;
            const rows = data.records || [];
            pagination.innerHTML = '';
            if (!rows.length) {
                tbody.innerHTML = '<tr><td colspan="14" class="text-muted">No DTR records found.</td></tr>';
                return;
            }
            tbody.innerHTML = rows.map(r => `<tr>
                <td>${esc(r.date)}</td><td>${esc(r.employee_id)}</td><td>${esc(r.employee_name)}</td><td>${esc(r.department)}</td>
                <td>${esc(r.schedule)}</td><td>${esc(r.time_in)}</td><td>${esc(r.time_out)}</td><td>${esc(r.total_hours)}</td>
                <td>${esc(r.late)}</td><td>${esc(r.undertime)}</td><td>${esc(r.overtime)}</td><td>${esc(r.status)}</td>
                <td>${esc(r.remarks)}</td><td><a class="btn btn--ghost btn--sm" href="${esc(r.url)}">View</a></td>
            </tr>`).join('');
        } catch (e) {}
    }

    document.getElementById('dtr-search').addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(liveSearch, 280);
    });
    ['dtr-employee','dtr-dept','dtr-from','dtr-to','dtr-status'].forEach(id => {
        document.getElementById(id).addEventListener('change', liveSearch);
    });
})();
</script>
@endpush
