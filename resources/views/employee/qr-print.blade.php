<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>QR — {{ $user->employee_id }}</title>
    <style>
        body { font-family: system-ui, sans-serif; text-align: center; padding: 2rem; }
        img { width: 280px; height: 280px; }
        h1 { font-size: 1.25rem; margin: 1rem 0 .25rem; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
    <button onclick="window.print()">Print</button>
    <div>
        <img src="data:{{ $qrMime }};base64,{{ $qrImage }}" alt="QR">
        <h1>{{ $user->displayName() }}</h1>
        <div>{{ $user->employee_id }}</div>
        <div>{{ $user->department }} · {{ $user->position }}</div>
    </div>
</body>
</html>
