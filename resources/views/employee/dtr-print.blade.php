<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>@media print { .no-print { display:none !important; } }</style>
</head>
<body style="padding:1.5rem;">
    <div class="no-print" style="margin-bottom:1rem;">
        <button onclick="window.print()" class="btn btn--primary">Print</button>
        <a href="{{ route('employee.dtr', ['month' => $month]) }}" class="btn btn--ghost">Back</a>
    </div>
    @include('employee.dtr-pdf')
</body>
</html>
