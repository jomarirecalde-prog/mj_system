<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        .meta { color: #64748b; margin-bottom: 16px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d7e0ea; padding: 6px 8px; text-align: left; }
        th { background: #eef2f6; font-size: 10px; text-transform: uppercase; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="meta">Generated {{ $generatedAt ?? ph_datetime(now()) }} · {{ setting('organization_name') }}</p>
    <table>
        <thead><tr>@foreach($headers as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
        <tbody>
        @foreach($rows as $row)
            <tr>@foreach($row as $cell)<td>{{ $cell ?? '' }}</td>@endforeach</tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
