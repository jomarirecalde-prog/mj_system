@extends('layouts.app')

@section('title', 'Backups')

@section('content')
<div class="page-header">
    <div><h1>Database backups</h1><p class="page-header__meta">Create and restore SQL backups</p></div>
    <form action="{{ route('backups.create') }}" method="post">@csrf<button type="submit" class="btn btn--primary">Create backup</button></form>
</div>

<div class="card">
    <div class="card__body table-wrap">
        @if($backups->isEmpty())
            <div class="empty-state"><p class="empty-state__title">No backups yet</p><p class="text-muted">Create your first backup to protect inventory data.</p></div>
        @else
            <table class="data-table">
                <thead><tr><th>File</th><th>Size</th><th>Created</th><th>By</th><th></th></tr></thead>
                <tbody>
                @foreach($backups as $backup)
                    <tr>
                        <td>{{ $backup->filename }}</td>
                        <td>{{ number_format(($backup->size ?? 0) / 1024, 1) }} KB</td>
                        <td>{{ ph_datetime($backup->created_at) }}</td>
                        <td>{{ $backup->creator?->displayName() ?? '—' }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('backups.download', $backup) }}" class="btn btn--secondary btn--sm">Download</a>
                                <form action="{{ route('backups.restore', $backup) }}" method="post" data-confirm="Restore this backup? Current data will be replaced." data-confirm-title="Restore database" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="confirm" value="1">
                                    <button type="submit" class="btn btn--danger btn--sm">Restore</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
