<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Employee Portal</title>
    @php $pwaAppTitle = 'Employee Portal'; @endphp
    @include('partials.pwa-head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body class="app-body">
@php $user = auth()->user(); @endphp

<div class="sidebar-backdrop" id="sidebar-backdrop"></div>

<aside class="sidebar" id="app-sidebar" aria-label="Employee navigation">
    <div class="sidebar__brand">
        <div class="sidebar__brand-title">Employee Portal</div>
        <div class="sidebar__brand-sub">{{ setting('organization_name', 'QR Inventory') }}</div>
    </div>
    <nav class="sidebar__nav">
        <a href="{{ route('employee.dashboard') }}" class="sidebar__link {{ request()->routeIs('employee.dashboard') || request()->routeIs('employee.live') ? 'is-active' : '' }}">Dashboard</a>
        <a href="{{ route('employee.attendance') }}" class="sidebar__link {{ request()->routeIs('employee.attendance') ? 'is-active' : '' }}">My Attendance</a>
        <a href="{{ route('employee.dtr') }}" class="sidebar__link {{ request()->routeIs('employee.dtr*') ? 'is-active' : '' }}">My DTR</a>
        <a href="{{ route('employee.calendar') }}" class="sidebar__link {{ request()->routeIs('employee.calendar*') ? 'is-active' : '' }}">Attendance Calendar</a>
        <a href="{{ route('employee.schedule') }}" class="sidebar__link {{ request()->routeIs('employee.schedule') ? 'is-active' : '' }}">My Schedule</a>
        <a href="{{ route('employee.corrections.index') }}" class="sidebar__link {{ request()->routeIs('employee.corrections.*') ? 'is-active' : '' }}">DTR Correction Requests</a>
        <a href="{{ route('employee.qr') }}" class="sidebar__link {{ request()->routeIs('employee.qr*') ? 'is-active' : '' }}">My QR Code</a>
        <a href="{{ route('employee.notifications') }}" class="sidebar__link {{ request()->routeIs('employee.notifications*') ? 'is-active' : '' }}">Notifications</a>
        <a href="{{ route('employee.profile') }}" class="sidebar__link {{ request()->routeIs('employee.profile*') ? 'is-active' : '' }}">My Profile</a>
        <a href="{{ route('employee.password.edit') }}" class="sidebar__link {{ request()->routeIs('employee.password.edit') || request()->routeIs('employee.password.change') ? 'is-active' : '' }}">Change Password</a>
    </nav>
    <div class="sidebar__footer">
        Signed in as <strong>{{ $user->displayName() }}</strong><br>
        <span class="text-muted">{{ $user->employee_id }}</span>
    </div>
</aside>

<div class="app-main">
    <header class="topbar no-print">
        <button type="button" class="topbar__toggle" id="sidebar-toggle" aria-label="Toggle menu">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div class="topbar__search" style="opacity:.7;">
            <span class="text-muted" style="font-size:.9rem;">Personal attendance &amp; DTR</span>
        </div>
        <div class="topbar__actions">
            <a href="{{ route('employee.notifications') }}" class="topbar__btn" aria-label="Notifications">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <span class="topbar__badge" id="notification-badge" data-poll-url="{{ route('employee.notifications.unread') }}">0</span>
            </a>
            @include('partials.pwa-install-button', ['class' => 'btn btn--ghost btn--sm', 'showIcon' => false])
            <div class="topbar__user">
                <span class="topbar__avatar">{{ strtoupper(substr($user->displayName(), 0, 1)) }}</span>
                <span>{{ $user->displayName() }}</span>
            </div>
            <form action="{{ route('employee.logout') }}" method="post" class="mb-0 logout-form">
                @csrf
                <button type="submit" class="btn btn--ghost btn--sm">Logout</button>
            </form>
        </div>
    </header>

    <main class="page-content">
        @include('partials.flash')
        @include('partials.alerts')
        @yield('content')
    </main>
</div>

<div id="toast-container" class="toast-container" aria-live="polite"></div>

@include('partials.logout-confirmation-modal')

<script src="{{ asset('js/app.js') }}"></script>
<script src="{{ asset('js/pwa.js') }}"></script>
@stack('scripts')
</body>
</html>
