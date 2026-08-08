<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ setting('organization_name', 'QR Inventory') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body class="app-body">
@php
    $user = auth()->user();
    $isAdmin = $user->isAdmin();
    $canModify = $user->canModifyInventory();
@endphp

<div class="sidebar-backdrop" id="sidebar-backdrop"></div>

<aside class="sidebar" id="app-sidebar" aria-label="Main navigation">
    <div class="sidebar__brand">
        <div class="sidebar__brand-title">{{ setting('organization_name', 'QR Inventory') }}</div>
        <div class="sidebar__brand-sub">Inventory Management</div>
    </div>
    <nav class="sidebar__nav">
        <a href="{{ route('dashboard') }}" class="sidebar__link {{ request()->routeIs('dashboard*') ? 'is-active' : '' }}">
            <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            Dashboard
        </a>
        <a href="{{ route('inventory.index') }}" class="sidebar__link {{ request()->routeIs('inventory.*') ? 'is-active' : '' }}">
            <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            Inventory
        </a>
        <a href="{{ route('qr.scan') }}" class="sidebar__link {{ request()->routeIs('qr.scan*') ? 'is-active' : '' }}">
            <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            Scan QR
        </a>

        @if($canModify)
            <div class="sidebar__section">Operations</div>
            <a href="{{ route('purchases.index') }}" class="sidebar__link {{ request()->routeIs('purchases.*') ? 'is-active' : '' }}">Purchases / Receiving</a>
            <a href="{{ route('pos.terminal') }}" class="sidebar__link {{ request()->routeIs('pos.*') ? 'is-active' : '' }}">Point of Sale</a>
            <a href="{{ route('qr.batch') }}" class="sidebar__link {{ request()->routeIs('qr.batch*') ? 'is-active' : '' }}">Batch QR Labels</a>

            <div class="sidebar__section">DTR &amp; Attendance</div>
            <a href="{{ route('employees.index') }}" class="sidebar__link {{ request()->routeIs('employees.*') ? 'is-active' : '' }}">Employees</a>
            <a href="{{ route('attendance.dashboard') }}" class="sidebar__link {{ request()->routeIs('attendance.dashboard') || request()->routeIs('attendance.live') ? 'is-active' : '' }}">Attendance Dashboard</a>
            <a href="{{ route('attendance.scanner') }}" class="sidebar__link {{ request()->routeIs('attendance.scanner*') ? 'is-active' : '' }}">QR Attendance Scanner</a>
            <a href="{{ route('attendance.today') }}" class="sidebar__link {{ request()->routeIs('attendance.today') || request()->routeIs('attendance.currently-in') ? 'is-active' : '' }}">Today's Attendance</a>
            <a href="{{ route('attendance.records') }}" class="sidebar__link {{ request()->routeIs('attendance.records*') ? 'is-active' : '' }}">DTR Records</a>
            <a href="{{ route('attendance.monthly') }}" class="sidebar__link {{ request()->routeIs('attendance.monthly') ? 'is-active' : '' }}">Monthly DTR</a>
            <a href="{{ route('attendance.schedules.index') }}" class="sidebar__link {{ request()->routeIs('attendance.schedules.*') || request()->routeIs('attendance.shifts.*') ? 'is-active' : '' }}">Employee Schedules</a>
            @if($isAdmin)
                <a href="{{ route('attendance.corrections.index') }}" class="sidebar__link {{ request()->routeIs('attendance.corrections.*') || request()->routeIs('attendance.correction-requests.*') ? 'is-active' : '' }}">DTR Corrections</a>
            @endif
            <a href="{{ route('attendance.reports.index') }}" class="sidebar__link {{ request()->routeIs('attendance.reports.*') ? 'is-active' : '' }}">Attendance Reports</a>
            <a href="{{ route('attendance.scan-logs') }}" class="sidebar__link {{ request()->routeIs('attendance.scan-logs') ? 'is-active' : '' }}">QR Scan Logs</a>
            @if($isAdmin)
                <a href="{{ route('attendance.qr.index') }}" class="sidebar__link {{ request()->routeIs('attendance.qr.*') ? 'is-active' : '' }}">Employee QR Codes</a>
                <a href="{{ route('attendance.settings.edit') }}" class="sidebar__link {{ request()->routeIs('attendance.settings*') ? 'is-active' : '' }}">Attendance Settings</a>
                <a href="{{ route('attendance.audit-logs') }}" class="sidebar__link {{ request()->routeIs('attendance.audit-logs') ? 'is-active' : '' }}">Attendance Audit Logs</a>
            @endif
        @endif

        <div class="sidebar__section">Master Data</div>
        <a href="{{ route('categories.index') }}" class="sidebar__link {{ request()->routeIs('categories.*') ? 'is-active' : '' }}">Categories</a>
        <a href="{{ route('locations.index') }}" class="sidebar__link {{ request()->routeIs('locations.*') ? 'is-active' : '' }}">Locations</a>
        <a href="{{ route('departments.index') }}" class="sidebar__link {{ request()->routeIs('departments.*') ? 'is-active' : '' }}">Departments</a>
        <a href="{{ route('suppliers.index') }}" class="sidebar__link {{ request()->routeIs('suppliers.*') ? 'is-active' : '' }}">Suppliers</a>

        <div class="sidebar__section">Insights</div>
        <a href="{{ route('reports.index') }}" class="sidebar__link {{ request()->routeIs('reports.*') ? 'is-active' : '' }}">Reports</a>
        <a href="{{ route('notifications.index') }}" class="sidebar__link {{ request()->routeIs('notifications.*') ? 'is-active' : '' }}">Notifications</a>

        @if($isAdmin)
            <div class="sidebar__section">Administration</div>
            <a href="{{ route('users.index') }}" class="sidebar__link {{ request()->routeIs('users.*') ? 'is-active' : '' }}">Users</a>
            <a href="{{ route('settings.edit') }}" class="sidebar__link {{ request()->routeIs('settings.*') ? 'is-active' : '' }}">Settings</a>
            <a href="{{ route('audit.index') }}" class="sidebar__link {{ request()->routeIs('audit.*') ? 'is-active' : '' }}">Audit Logs</a>
            <a href="{{ route('import.index') }}" class="sidebar__link {{ request()->routeIs('import.*') ? 'is-active' : '' }}">Import</a>
            <a href="{{ route('export.index') }}" class="sidebar__link">Export</a>
            <a href="{{ route('backups.index') }}" class="sidebar__link {{ request()->routeIs('backups.*') ? 'is-active' : '' }}">Backups</a>
        @endif
    </nav>
    <div class="sidebar__footer">
        Signed in as <strong>{{ $user->displayName() }}</strong><br>
        <span class="text-muted">{{ ucfirst($user->role) }}</span>
    </div>
</aside>

<div class="app-main">
    <header class="topbar no-print">
        <button type="button" class="topbar__toggle" id="sidebar-toggle" aria-label="Toggle menu">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div class="topbar__search">
            <svg class="topbar__search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="search" id="global-search" placeholder="Search inventory…" autocomplete="off"
                   data-search-url="{{ route('inventory.index') }}"
                   value="{{ request('search') }}">
        </div>
        <div class="topbar__actions">
            <a href="{{ route('notifications.index') }}" class="topbar__btn" aria-label="Notifications">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <span class="topbar__badge" id="notification-badge" data-poll-url="{{ route('notifications.unread') }}">0</span>
            </a>
            <div class="topbar__user">
                <span class="topbar__avatar">{{ strtoupper(substr($user->displayName(), 0, 1)) }}</span>
                <span>{{ $user->displayName() }}</span>
            </div>
            <form action="{{ route('logout') }}" method="post" class="mb-0">
                @csrf
                <button type="submit" class="btn btn--ghost btn--sm">Sign out</button>
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

<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
