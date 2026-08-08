@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="page-header">
    <div><h1>Notifications</h1></div>
    <form action="{{ route('notifications.read-all') }}" method="post">@csrf<button type="submit" class="btn btn--secondary">Mark all read</button></form>
</div>

<div class="card">
    <div class="card__body">
        @if($notifications->isEmpty())
            <div class="empty-state"><p class="empty-state__title">No notifications</p><p class="text-muted">You are all caught up.</p></div>
        @else
            <ul style="list-style:none;margin:0;padding:0;">
                @foreach($notifications as $note)
                    <li style="padding:1rem;border-bottom:1px solid var(--border);{{ $note->is_read ? 'opacity:0.75;' : '' }}">
                        <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
                            <div>
                                <strong>{{ $note->title }}</strong>
                                @if(!$note->is_read)<span class="badge badge--borrowed" style="margin-left:0.35rem;">New</span>@endif
                                <p class="text-muted mb-0" style="margin-top:0.35rem;">{{ $note->message }}</p>
                                <p class="text-muted" style="font-size:0.8rem;margin-top:0.35rem;">{{ ph_datetime($note->created_at) }}</p>
                            </div>
                            <div class="btn-group">
                                @if($note->link)<a href="{{ $note->link }}" class="btn btn--ghost btn--sm">Open</a>@endif
                                @if(!$note->is_read)
                                    <form action="{{ route('notifications.read', $note) }}" method="post">@csrf<button type="submit" class="btn btn--secondary btn--sm">Mark read</button></form>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
            @include('partials.pagination', ['paginator' => $notifications])
        @endif
    </div>
</div>
@endsection
