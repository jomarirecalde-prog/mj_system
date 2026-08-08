@extends('layouts.print')

@section('title', $title)

@section('content')
<div class="print-sheet">
    <h1>{{ $title }}</h1>
    <p class="text-muted">{{ setting('organization_name') }} · {{ ph_datetime(now()) }}</p>
    <table class="data-table">
        <thead><tr>@foreach($headers as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
        <tbody>
        @foreach($rows as $row)
            <tr>@foreach($row as $cell)<td>{{ $cell ?? '—' }}</td>@endforeach</tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
