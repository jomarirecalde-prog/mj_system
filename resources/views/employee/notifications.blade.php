@extends('layouts.employee')

@section('title', 'Notifications')

@section('content')
<div class="page-header">
    <div>
        <h1>Notifications</h1>
        <p class="page-header__meta">Attendance and DTR updates for your account</p>
    </div>
    <form method="post" action="{{ route('employee.notifications.read-all') }}">
        @csrf
        <button class="btn btn--secondary" type="submit">Mark all read</button>
    </form>
</div>

<div class="card">
    <div class="card__body">
        <ul style="list-style:none;margin:0;padding:0;">
            @forelse($notifications as $n)
                <li style="padding:1rem 0;border-bottom:1px solid var(--border,#e2e8f0);{{ $n->is_read ? '' : 'background:#f8fafc;' }}">
                    <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;">
                        <div>
                            <strong>{{ $n->title }}</strong>
                            <div class="text-muted" style="font-size:.85rem;">{{ ph_datetime($n->created_at) }}</div>
                            <p style="margin:.35rem 0 0;">{{ $n->message }}</p>
                            @if($n->link)
                                <a href="{{ $n->link }}" style="font-size:.9rem;">Open</a>
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
            @empty
                <li class="text-muted">No notifications yet.</li>
            @endforelse
        </ul>
        @include('partials.pagination', ['paginator' => $notifications])
    </div>
</div>
@endsection
