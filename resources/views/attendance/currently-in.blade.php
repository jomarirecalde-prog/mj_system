@extends('layouts.app')

@section('title', 'Currently Time In')

@section('content')
<div class="page-header">
    <div>
        <h1>Currently Time In</h1>
        <p class="page-header__meta">Employees on duty right now · {{ $date }} · <span id="ci-clock"></span></p>
    </div>
    <a href="{{ route('attendance.dashboard') }}" class="btn btn--secondary">Dashboard</a>
</div>

<div class="card">
    <div class="card__body table-wrap">
        <table class="data-table" id="ci-table">
            <thead><tr><th>Employee</th><th>Department</th><th>Time In</th><th>Duration</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($records as $row)
                @php $mins = $row->time_in ? $row->time_in->diffInMinutes(now('Asia/Manila')) : 0; @endphp
                <tr>
                    <td>{{ $row->user?->displayName() }}</td>
                    <td>{{ $row->user?->department ?? '—' }}</td>
                    <td>{{ ph_datetime($row->time_in, 'h:i A') }}</td>
                    <td>{{ intdiv($mins, 60) }}h {{ sprintf('%02d', $mins % 60) }}m</td>
                    <td>{{ $row->statusLabel() }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">Nobody is currently timed in.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const liveUrl = @json(route('attendance.live'));
    async function refresh() {
        const res = await fetch(liveUrl, { headers: { Accept: 'application/json' } });
        const data = await res.json();
        if (!data.success) return;
        document.getElementById('ci-clock').textContent = data.server_time;
        const tbody = document.querySelector('#ci-table tbody');
        const rows = data.currently_in || [];
        const esc = (v) => String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        tbody.innerHTML = rows.length
            ? rows.map(r => `<tr><td>${esc(r.employee)}</td><td>${esc(r.department||'—')}</td><td>${esc(r.time_in)}</td><td>${esc(r.duration)}</td><td>${esc(r.status)}</td></tr>`).join('')
            : '<tr><td colspan="5" class="text-muted">Nobody is currently timed in.</td></tr>';
    }
    refresh();
    setInterval(refresh, 10000);
})();
</script>
@endpush
