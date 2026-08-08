@extends('layouts.app')

@section('title', 'Import preview')

@section('content')
<div class="page-header">
    <div><h1>Import preview</h1><p class="page-header__meta">{{ count($rows) }} row(s) ready</p></div>
    <a href="{{ route('import.index') }}" class="btn btn--secondary">Upload another</a>
</div>

<div class="card mb-2">
    <div class="card__body table-wrap" style="max-height:420px;overflow:auto;">
        @if(empty($rows))
            <div class="empty-state"><p class="empty-state__title">No rows parsed</p></div>
        @else
            <table class="data-table">
                <thead><tr>@foreach(array_keys($rows[0]) as $col)<th>{{ $col }}</th>@endforeach</tr></thead>
                <tbody>
                @foreach($rows as $row)
                    <tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

<form method="post" action="{{ route('import.confirm') }}" data-confirm="Import {{ count($rows) }} row(s) into inventory? This cannot be undone easily." data-confirm-title="Confirm import">
    @csrf
    <button type="submit" class="btn btn--primary" @disabled(empty($rows))>Confirm import</button>
</form>
@endsection
