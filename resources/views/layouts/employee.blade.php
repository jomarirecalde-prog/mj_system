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
@php
    $user = auth()->user();
    $orgName = setting('organization_name', 'QR Inventory');
    $orgWords = preg_split('/\s+/', trim($orgName)) ?: [];
    $orgInitials = strtoupper(collect($orgWords)->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->join(''));
    if ($orgInitials === '') {
        $orgInitials = 'EP';
    }

    $displayName = $user->displayName();
    $nameParts = preg_split('/\s+/', trim($displayName)) ?: [];
    $userInitials = strtoupper(
        count($nameParts) >= 2
            ? mb_substr($nameParts[0], 0, 1) . mb_substr($nameParts[array_key_last($nameParts)], 0, 1)
            : mb_substr($displayName, 0, 2)
    );

    $overviewActive = request()->routeIs('employee.dashboard', 'employee.live');
    $attendanceActive = request()->routeIs(
        'employee.attendance',
        'employee.dtr*',
        'employee.calendar*',
        'employee.schedule',
        'employee.corrections.*',
        'employee.official-time.*'
    );
    $accountActive = request()->routeIs(
        'employee.qr*',
        'employee.notifications*',
        'employee.profile*',
        'employee.password.*'
    );
@endphp

<div class="sidebar-backdrop" id="sidebar-backdrop" aria-hidden="true"></div>

<aside class="sidebar" id="app-sidebar" aria-label="Employee navigation">
    <div class="sidebar__header">
        <a href="{{ route('employee.dashboard') }}" class="sidebar__brand" title="Employee Portal">
            <span class="sidebar__brand-mark" aria-hidden="true">{{ $orgInitials }}</span>
            <span class="sidebar__brand-text">
                <span class="sidebar__brand-title">Employee Portal</span>
                <span class="sidebar__brand-sub">{{ $orgName }}</span>
            </span>
        </a>
        <button type="button" class="sidebar__collapse-btn" id="sidebar-collapse" aria-label="Collapse sidebar" aria-expanded="true">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
        </button>
    </div>

    <nav class="sidebar__nav" aria-label="Sidebar menu">
        <div class="nav-group {{ $overviewActive ? 'is-route-active' : '' }}" data-nav-group="emp-overview">
            <button type="button" class="nav-group__trigger" data-nav-trigger aria-expanded="{{ $overviewActive ? 'true' : 'false' }}">
                <span class="nav-group__label">Overview</span>
                <svg class="nav-group__chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="nav-group__panel" data-nav-panel>
                <a href="{{ route('employee.dashboard') }}" class="sidebar__link {{ request()->routeIs('employee.dashboard', 'employee.live') ? 'is-active' : '' }}" data-tooltip="Dashboard">
                    <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <span class="sidebar__label">Dashboard</span>
                </a>
            </div>
        </div>

        <div class="nav-group {{ $attendanceActive ? 'is-route-active' : '' }}" data-nav-group="emp-attendance">
            <button type="button" class="nav-group__trigger" data-nav-trigger aria-expanded="{{ $attendanceActive ? 'true' : 'false' }}">
                <span class="nav-group__label">Attendance</span>
                <svg class="nav-group__chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="nav-group__panel" data-nav-panel>
                <a href="{{ route('employee.attendance') }}" class="sidebar__link {{ request()->routeIs('employee.attendance') ? 'is-active' : '' }}" data-tooltip="My Attendance">
                    <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="sidebar__label">My Attendance</span>
                </a>
                <a href="{{ route('employee.dtr') }}" class="sidebar__link {{ request()->routeIs('employee.dtr*') ? 'is-active' : '' }}" data-tooltip="My DTR">
                    <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="sidebar__label">My DTR</span>
                </a>
                <a href="{{ route('employee.calendar') }}" class="sidebar__link {{ request()->routeIs('employee.calendar*') ? 'is-active' : '' }}" data-tooltip="Attendance Calendar">
                    <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span class="sidebar__label">Calendar</span>
                </a>
                <a href="{{ route('employee.schedule') }}" class="sidebar__link {{ request()->routeIs('employee.schedule') ? 'is-active' : '' }}" data-tooltip="My Schedule">
                    <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="sidebar__label">My Schedule</span>
                </a>
                <a href="{{ route('employee.corrections.index') }}" class="sidebar__link {{ request()->routeIs('employee.corrections.*') ? 'is-active' : '' }}" data-tooltip="DTR Corrections">
                    <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    <span class="sidebar__label">Corrections</span>
                </a>
                <a href="{{ route('employee.official-time.index') }}" class="sidebar__link {{ request()->routeIs('employee.official-time.*') ? 'is-active' : '' }}" data-tooltip="Official Time Request">
                    <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="sidebar__label">Official Time</span>
                </a>
            </div>
        </div>

        <div class="nav-group {{ $accountActive ? 'is-route-active' : '' }}" data-nav-group="emp-account">
            <button type="button" class="nav-group__trigger" data-nav-trigger aria-expanded="{{ $accountActive ? 'true' : 'false' }}">
                <span class="nav-group__label">Account</span>
                <svg class="nav-group__chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="nav-group__panel" data-nav-panel>
                <a href="{{ route('employee.qr') }}" class="sidebar__link {{ request()->routeIs('employee.qr*') ? 'is-active' : '' }}" data-tooltip="My QR Code">
                    <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    <span class="sidebar__label">My QR Code</span>
                </a>
                <a href="{{ route('employee.notifications') }}" class="sidebar__link {{ request()->routeIs('employee.notifications*') ? 'is-active' : '' }}" data-tooltip="Notifications">
                    <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span class="sidebar__label">Notifications</span>
                </a>
                <a href="{{ route('employee.profile') }}" class="sidebar__link {{ request()->routeIs('employee.profile*') ? 'is-active' : '' }}" data-tooltip="My Profile">
                    <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span class="sidebar__label">My Profile</span>
                </a>
                <a href="{{ route('employee.password.edit') }}" class="sidebar__link {{ request()->routeIs('employee.password.*') ? 'is-active' : '' }}" data-tooltip="Change Password">
                    <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <span class="sidebar__label">Change Password</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="sidebar__user">
        <span class="sidebar__user-avatar" aria-hidden="true">{{ $userInitials }}</span>
        <span class="sidebar__user-info">
            <span class="sidebar__user-name" title="{{ $displayName }}">{{ $displayName }}</span>
            <span class="sidebar__user-role">{{ $user->employee_id }}</span>
        </span>
    </div>
</aside>

<div class="app-main" id="app-main">
    <header class="topbar no-print">
        <div class="topbar__start">
            <button type="button" class="topbar__toggle" id="sidebar-toggle" aria-label="Open navigation menu" aria-controls="app-sidebar" aria-expanded="false">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div class="topbar__page-title">
                <span class="topbar__page-label">@yield('title', 'Dashboard')</span>
                <span class="topbar__page-sub">Personal attendance &amp; DTR</span>
            </div>
        </div>

        <div class="topbar__actions">
            <a href="{{ route('employee.notifications') }}" class="topbar__btn topbar__btn--notify" id="notification-btn" aria-label="Notifications">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <span class="topbar__badge" id="notification-badge" data-poll-url="{{ route('employee.notifications.unread') }}">0</span>
            </a>

            <div class="account-menu" id="account-menu">
                <button type="button" class="account-menu__trigger" id="account-menu-trigger" aria-haspopup="true" aria-expanded="false" aria-controls="account-menu-dropdown">
                    <span class="account-menu__avatar" aria-hidden="true">{{ $userInitials }}</span>
                    <span class="account-menu__name">{{ $displayName }}</span>
                    <svg class="account-menu__caret" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="account-menu__dropdown" id="account-menu-dropdown" role="menu" hidden>
                    <div class="account-menu__header">
                        <span class="account-menu__avatar account-menu__avatar--lg" aria-hidden="true">{{ $userInitials }}</span>
                        <div class="account-menu__meta">
                            <span class="account-menu__display">{{ $displayName }}</span>
                            <span class="account-menu__role">{{ $user->employee_id }}</span>
                        </div>
                    </div>
                    <a href="{{ route('employee.profile') }}" class="account-menu__item" role="menuitem">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        My Profile
                    </a>
                    <a href="{{ route('employee.password.edit') }}" class="account-menu__item" role="menuitem">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        Change Password
                    </a>
                    <a href="{{ route('employee.qr') }}" class="account-menu__item" role="menuitem">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                        My QR Code
                    </a>
                    @include('partials.pwa-install-button', ['showIcon' => true])
                    <form action="{{ route('employee.logout') }}" method="post" class="account-menu__logout logout-form">
                        @csrf
                        <button type="submit" class="account-menu__item account-menu__item--danger" role="menuitem">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            Sign Out
                        </button>
                    </form>
                </div>
            </div>
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
