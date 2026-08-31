@extends('layouts.employee')

@section('title', 'Notifications')

@section('content')
<div class="page-header">
    <div>
        <h1>Notifications</h1>
        <p class="page-header__meta">Attendance and DTR updates for your account</p>
    </div>
    @if($notifications->count() > 0)
        <form method="post" action="{{ route('employee.notifications.read-all') }}">
            @csrf
            <button class="btn btn--secondary" type="submit">Mark all read</button>
        </form>
    @endif
</div>

<div class="card">
    <div class="card__body">
        @if($notifications->count() > 0)
            <ul class="emp-notif-list">
                @foreach($notifications as $n)
                    <li class="emp-notif-item {{ $n->is_read ? '' : 'is-unread' }}">
                        <div class="emp-notif-item__row">
                            <div>
                                <div class="emp-notif-item__title">{{ $n->title }}</div>
                                <div class="emp-notif-item__time">{{ ph_datetime($n->created_at) }}</div>
                                <p class="emp-notif-item__message">{{ $n->message }}</p>
                                @if($n->link)
                                    <a href="{{ $n->link }}" class="emp-notif-item__link">
                                        Open
                                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                @endif
                            </div>
                            @unless($n->is_read)
                                <form method="post" action="{{ route('employee.notifications.read', $n) }}">
                                    @csrf
                                    <button class="btn btn--ghost btn--sm" type="submit">Mark read</button>
                                </form>
                            @endunless
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="empty-state">
                <div class="empty-state__title">No notifications yet</div>
                <p>When there are updates about your attendance or DTR, they will appear here.</p>
            </div>
        @endif
        @include('partials.pagination', ['paginator' => $notifications])
    </div>
</div>
@endsection
