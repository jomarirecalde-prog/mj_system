@extends('layouts.print')

@section('title', $title)

@section('content')
<h1>{{ $title }}</h1>
<p>Generated {{ now('Asia/Manila')->format('M d, Y h:i A') }} (Asia/Manila)</p>
<table class="data-table">
    <thead><tr>@foreach($headers as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
    <tbody>
    @foreach($rows as $row)
        <tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
    @endforeach
    </tbody>
</table>
<script>window.print()</script>
@endsection
