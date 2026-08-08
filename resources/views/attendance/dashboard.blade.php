@extends('layouts.app')

@section('title', 'Attendance Dashboard')

@section('content')
<div class="page-header">
    <div>
        <h1>Today's Attendance</h1>
        <p class="page-header__meta">{{ \Carbon\Carbon::parse($date)->timezone('Asia/Manila')->format('l, F j, Y') }} · Philippine Time · <span id="server-clock">—</span></p>
    </div>
    <div class="btn-group">
        <a href="{{ route('attendance.scanner') }}" class="btn btn--primary">Open QR Scanner</a>
        <a href="{{ route('attendance.today') }}" class="btn btn--secondary">Today's List</a>
    </div>
</div>

<div class="stat-grid mb-2" id="attendance-stats">
    <div class="stat-card"><div class="stat-card__label">Total Employees</div><div class="stat-card__value" data-stat="total">{{ $counts['total'] }}</div></div>
    <div class="stat-card"><div class="stat-card__label">Present</div><div class="stat-card__value" data-stat="present">{{ $counts['present'] }}</div></div>
    <div class="stat-card"><div class="stat-card__label">Late</div><div class="stat-card__value" data-stat="late">{{ $counts['late'] }}</div></div>
    <div class="stat-card"><div class="stat-card__label">Absent</div><div class="stat-card__value" data-stat="absent">{{ $counts['absent'] }}</div></div>
    <div class="stat-card"><div class="stat-card__label">On Leave</div><div class="stat-card__value" data-stat="on_leave">{{ $counts['on_leave'] }}</div></div>
    <div class="stat-card"><div class="stat-card__label">Currently Time In</div><div class="stat-card__value" data-stat="currently_in">{{ $counts['currently_in'] }}</div></div>
    <div class="stat-card"><div class="stat-card__label">Already Time Out</div><div class="stat-card__value" data-stat="timed_out">{{ $counts['timed_out'] }}</div></div>
    <div class="stat-card"><div class="stat-card__label">Incomplete DTR</div><div class="stat-card__value" data-stat="incomplete">{{ $counts['incomplete'] }}</div></div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card__header"><h2 class="card__title">Currently Time In</h2></div>
        <div class="card__body table-wrap">
            <table class="data-table" id="currently-in-table">
                <thead><tr><th>Employee</th><th>Department</th><th>Time In</th><th>Duration</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($currentlyIn as $row)
                    @php $mins = $row->time_in ? $row->time_in->diffInMinutes(now('Asia/Manila')) : 0; @endphp
                    <tr>
                        <td>{{ $row->user?->displayName() }}</td>
                        <td>{{ $row->user?->department ?? '—' }}</td>
                        <td>{{ ph_datetime($row->time_in, 'h:i A') }}</td>
                        <td>{{ intdiv($mins, 60) }}h {{ $mins % 60 }}m</td>
                        <td><span class="badge badge--available">{{ $row->statusLabel() }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">No employees currently timed in.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card__header"><h2 class="card__title">Recent punches</h2></div>
        <div class="card__body table-wrap">
            <table class="data-table">
                <thead><tr><th>Employee</th><th>Time In</th><th>Time Out</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($recent as $row)
                    <tr>
                        <td>{{ $row->user?->displayName() }}</td>
                        <td>{{ $row->time_in ? ph_datetime($row->time_in, 'h:i A') : '—' }}</td>
                        <td>{{ $row->time_out ? ph_datetime($row->time_out, 'h:i A') : '—' }}</td>
                        <td>{{ $row->statusLabel() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted">No punches yet today.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const liveUrl = @json(route('attendance.live'));
    const clock = document.getElementById('server-clock');
    const tbody = document.querySelector('#currently-in-table tbody');

    async function refresh() {
        try {
            const res = await fetch(liveUrl, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (!data.success) return;
            clock.textContent = data.server_time;
            Object.entries(data.counts || {}).forEach(([k, v]) => {
                const el = document.querySelector('[data-stat="' + k + '"]');
                if (el) el.textContent = v;
            });
            if (!tbody) return;
            const rows = data.currently_in || [];
            if (!rows.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-muted">No employees currently timed in.</td></tr>';
                return;
            }
            tbody.innerHTML = rows.map(r => '<tr>' +
                '<td>' + App.escapeHtml(r.employee) + '</td>' +
                '<td>' + App.escapeHtml(r.department || '—') + '</td>' +
                '<td>' + App.escapeHtml(r.time_in) + '</td>' +
                '<td>' + App.escapeHtml(r.duration) + '</td>' +
                '<td><span class="badge badge--available">' + App.escapeHtml(r.status) + '</span></td>' +
                '</tr>').join('');
        } catch (e) {}
    }

    if (!window.App) window.App = {};
    if (!App.escapeHtml) {
        App.escapeHtml = (v) => String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }
    refresh();
    setInterval(refresh, 10000);
})();
</script>
@endpush
