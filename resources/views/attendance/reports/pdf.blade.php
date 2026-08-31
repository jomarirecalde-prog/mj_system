<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 36pt 28pt 48pt; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #0f172a;
            line-height: 1.4;
        }
        .header {
            border-bottom: 2px solid #0f766e;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .org {
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            margin-bottom: 4px;
        }
        h1 {
            font-size: 16px;
            font-weight: 700;
            margin: 0 0 6px;
            color: #0f172a;
        }
        .meta {
            font-size: 9px;
            color: #64748b;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #f1f5f9;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #475569;
        }
        tr:nth-child(even) td { background: #f8fafc; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }
    </style>
</head>
<body>
    <div class="header">
        @if(function_exists('setting') && setting('organization_name'))
            <div class="org">{{ setting('organization_name') }}</div>
        @endif
        <h1>{{ $title }}</h1>
        <div class="meta">
            Generated: {{ now('Asia/Manila')->format('M d, Y h:i A') }} · Timezone: Asia/Manila
        </div>
    </div>

    <table>
        <thead>
            <tr>@foreach($headers as $h)<th>{{ $h }}</th>@endforeach</tr>
        </thead>
        <tbody>
        @forelse($rows as $row)
            <tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
        @empty
            <tr><td colspan="{{ count($headers) }}">No attendance data available for the selected period.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="footer">Attendance Report · {{ now('Asia/Manila')->format('Y-m-d H:i') }} PHT</div>
</body>
</html>
