<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        .meta { color: #555; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 5px 6px; text-align: left; }
        th { background: #f3f3f3; }
        .totals { margin-top: 14px; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">
        {{ $user->displayName() }} · {{ $user->employee_id }} · {{ $user->department }} · {{ $user->position }}
        · Generated {{ now('Asia/Manila')->format('M d, Y h:i A') }}
    </div>
    <table>
        <thead><tr>@foreach($headers as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
        <tbody>
        @foreach($rows as $row)
            <tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
        @endforeach
        </tbody>
    </table>
    <div class="totals">
        Present: {{ $totals['present'] }} ·
        Absent: {{ $totals['absent'] }} ·
        Late: {{ \App\Support\EmployeeAttendancePresenter::minutesToLabel($totals['late']) }} ·
        Undertime: {{ \App\Support\EmployeeAttendancePresenter::minutesToLabel($totals['undertime']) }} ·
        Overtime: {{ \App\Support\EmployeeAttendancePresenter::minutesToLabel($totals['overtime']) }} ·
        Hours: {{ \App\Support\EmployeeAttendancePresenter::minutesToLabel($totals['hours']) }}
    </div>
</body>
</html>
