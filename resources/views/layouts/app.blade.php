@if(is_app_navigation())
<meta name="page-title" content="@yield('title', 'Dashboard') — {{ setting('organization_name', 'QR Inventory') }}">
@stack('styles')
<main class="page-content" id="page-content">
    @include('partials.flash')
    @include('partials.alerts')
    @yield('content')
</main>
@stack('scripts')
@else
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ setting('organization_name', 'QR Inventory') }}</title>
    @include('partials.pwa-head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body class="app-body">
<div id="nav-progress" class="nav-progress" aria-hidden="true"><div class="nav-progress__bar"></div></div>
@php
    $user = auth()->user();
    $isAdmin = $user->isAdmin();
    $canModify = $user->canModifyInventory();

    $orgName = setting('organization_name', 'QR Inventory');
    $orgWords = preg_split('/\s+/', trim($orgName)) ?: [];
    $orgInitials = strtoupper(collect($orgWords)->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->join(''));
    if ($orgInitials === '') {
        $orgInitials = 'QR';
    }

    $displayName = $user->displayName();
    $nameParts = preg_split('/\s+/', trim($displayName)) ?: [];
    $userInitials = strtoupper(
        count($nameParts) >= 2
            ? mb_substr($nameParts[0], 0, 1) . mb_substr($nameParts[array_key_last($nameParts)], 0, 1)
            : mb_substr($displayName, 0, 2)
    );
    $userRole = ucfirst($user->role);

    $mainActive = request()->routeIs('dashboard*', 'inventory.*', 'qr.scan*');
    $operationsActive = request()->routeIs('purchases.*', 'pos.*', 'qr.batch*');
    $dtrActive = $canModify && request()->routeIs(
        'employees.*',
        'attendance.*',
        'admin.qr-stations.*'
    );
    $attendanceMonitoringActive = request()->routeIs(
        'attendance.dashboard',
        'attendance.live',
        'attendance.today',
        'attendance.currently-in',
        'attendance.records*',
        'attendance.monthly'
    );
    $scannerQrActive = request()->routeIs(
        'attendance.scanner*',
        'attendance.scan-logs',
        'attendance.qr.*',
        'admin.qr-stations.*'
    );
    $employeeMgmtActive = request()->routeIs(
        'employees.*',
        'attendance.schedules.*',
        'attendance.shifts.*',
        'attendance.corrections.*',
        'attendance.correction-requests.*'
    );
    $attendanceReportsActive = request()->routeIs(
        'attendance.reports.*',
        'attendance.settings*',
        'attendance.audit-logs'
    );
    $masterDataActive = request()->routeIs('categories.*', 'locations.*', 'departments.*', 'suppliers.*');
    $insightsActive = request()->routeIs('reports.*', 'notifications.*');
    $adminActive = $isAdmin && request()->routeIs('users.*', 'settings.*', 'audit.*', 'import.*', 'export.*', 'backups.*');
@endphp

<div class="sidebar-backdrop" id="sidebar-backdrop" aria-hidden="true"></div>

<aside class="sidebar" id="app-sidebar" aria-label="Main navigation">
    <div class="sidebar__header">
        <a href="{{ route('dashboard') }}" class="sidebar__brand" title="{{ $orgName }}">
            <span class="sidebar__brand-mark" aria-hidden="true">{{ $orgInitials }}</span>
            <span class="sidebar__brand-text">
                <span class="sidebar__brand-title">{{ $orgName }}</span>
                <span class="sidebar__brand-sub">Inventory Management System</span>
            </span>
        </a>
        <button type="button" class="sidebar__collapse-btn" id="sidebar-collapse" aria-label="Collapse sidebar" aria-expanded="true">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
        </button>
    </div>

    <nav class="sidebar__nav" aria-label="Sidebar menu">
        {{-- Main --}}
        <div class="nav-group {{ $mainActive ? 'is-route-active' : '' }}" data-nav-group="main">
            <button type="button" class="nav-group__trigger" data-nav-trigger aria-expanded="{{ $mainActive ? 'true' : 'false' }}">
                <span class="nav-group__label">Main</span>
                <svg class="nav-group__chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="nav-group__panel" data-nav-panel>
                <a href="{{ route('dashboard') }}" class="sidebar__link {{ request()->routeIs('dashboard*') ? 'is-active' : '' }}" data-tooltip="Dashboard">
                    <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <span class="sidebar__label">Dashboard</span>
                </a>
                <a href="{{ route('inventory.index') }}" class="sidebar__link {{ request()->routeIs('inventory.*') ? 'is-active' : '' }}" data-tooltip="Inventory">
                    <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <span class="sidebar__label">Inventory</span>
                </a>
                <a href="{{ route('qr.scan') }}" class="sidebar__link {{ request()->routeIs('qr.scan*') ? 'is-active' : '' }}" data-tooltip="Scan QR">
                    <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    <span class="sidebar__label">Scan QR</span>
                </a>
            </div>
        </div>

        @if($canModify)
            {{-- Operations --}}
            <div class="nav-group {{ $operationsActive ? 'is-route-active' : '' }}" data-nav-group="operations">
                <button type="button" class="nav-group__trigger" data-nav-trigger aria-expanded="{{ $operationsActive ? 'true' : 'false' }}">
                    <span class="nav-group__label">Operations</span>
                    <svg class="nav-group__chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="nav-group__panel" data-nav-panel>
                    <a href="{{ route('purchases.index') }}" class="sidebar__link {{ request()->routeIs('purchases.*') ? 'is-active' : '' }}" data-tooltip="Purchases / Receiving">
                        <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span class="sidebar__label">Purchases / Receiving</span>
                    </a>
                    <a href="{{ route('pos.terminal') }}" class="sidebar__link {{ request()->routeIs('pos.*') ? 'is-active' : '' }}" data-tooltip="Point of Sale">
                        <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span class="sidebar__label">Point of Sale</span>
                    </a>
                    <a href="{{ route('qr.batch') }}" class="sidebar__link {{ request()->routeIs('qr.batch*') ? 'is-active' : '' }}" data-tooltip="Batch QR Labels">
                        <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span class="sidebar__label">Batch QR Labels</span>
                    </a>
                </div>
            </div>

            {{-- DTR & Attendance --}}
            <div class="nav-group {{ $dtrActive ? 'is-route-active' : '' }}" data-nav-group="dtr-attendance">
                <button type="button" class="nav-group__trigger" data-nav-trigger aria-expanded="{{ $dtrActive ? 'true' : 'false' }}">
                    <span class="nav-group__label">DTR &amp; Attendance</span>
                    <svg class="nav-group__chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="nav-group__panel" data-nav-panel>
                    <div class="nav-subgroup {{ $attendanceMonitoringActive ? 'is-route-active' : '' }}" data-nav-group="dtr-monitoring">
                        <button type="button" class="nav-subgroup__trigger" data-nav-trigger aria-expanded="{{ $attendanceMonitoringActive ? 'true' : 'false' }}">
                            <span class="nav-subgroup__label">Attendance Monitoring</span>
                            <svg class="nav-group__chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div class="nav-subgroup__panel" data-nav-panel>
                            <a href="{{ route('attendance.dashboard') }}" class="sidebar__link sidebar__link--nested {{ request()->routeIs('attendance.dashboard') || request()->routeIs('attendance.live') ? 'is-active' : '' }}" data-nav-paths="/attendance,/attendance/live" data-tooltip="Attendance Dashboard">
                                <span class="sidebar__label">Attendance Dashboard</span>
                            </a>
                            <a href="{{ route('attendance.today') }}" class="sidebar__link sidebar__link--nested {{ request()->routeIs('attendance.today') || request()->routeIs('attendance.currently-in') ? 'is-active' : '' }}" data-nav-paths="/attendance/today,/attendance/currently-in" data-tooltip="Today's Attendance">
                                <span class="sidebar__label">Today's Attendance</span>
                            </a>
                            <a href="{{ route('attendance.records') }}" class="sidebar__link sidebar__link--nested {{ request()->routeIs('attendance.records*') ? 'is-active' : '' }}" data-tooltip="DTR Records">
                                <span class="sidebar__label">DTR Records</span>
                            </a>
                            <a href="{{ route('attendance.monthly') }}" class="sidebar__link sidebar__link--nested {{ request()->routeIs('attendance.monthly') ? 'is-active' : '' }}" data-tooltip="Monthly DTR">
                                <span class="sidebar__label">Monthly DTR</span>
                            </a>
                        </div>
                    </div>

                    <div class="nav-subgroup {{ $scannerQrActive ? 'is-route-active' : '' }}" data-nav-group="dtr-scanner">
                        <button type="button" class="nav-subgroup__trigger" data-nav-trigger aria-expanded="{{ $scannerQrActive ? 'true' : 'false' }}">
                            <span class="nav-subgroup__label">Scanner &amp; QR</span>
                            <svg class="nav-group__chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div class="nav-subgroup__panel" data-nav-panel>
                            <a href="{{ route('attendance.scanner') }}" class="sidebar__link sidebar__link--nested {{ request()->routeIs('attendance.scanner*') ? 'is-active' : '' }}" data-tooltip="QR Attendance Scanner">
                                <span class="sidebar__label">QR Attendance Scanner</span>
                            </a>
                            @if($isAdmin)
                                <a href="{{ route('admin.qr-stations.index') }}" class="sidebar__link sidebar__link--nested {{ request()->routeIs('admin.qr-stations.*') ? 'is-active' : '' }}" data-tooltip="QR Scanner Stations">
                                    <span class="sidebar__label">QR Scanner Stations</span>
                                </a>
                                <a href="{{ route('attendance.qr.index') }}" class="sidebar__link sidebar__link--nested {{ request()->routeIs('attendance.qr.*') ? 'is-active' : '' }}" data-tooltip="Employee QR Codes">
                                    <span class="sidebar__label">Employee QR Codes</span>
                                </a>
                            @endif
                            <a href="{{ route('attendance.scan-logs') }}" class="sidebar__link sidebar__link--nested {{ request()->routeIs('attendance.scan-logs') ? 'is-active' : '' }}" data-tooltip="QR Scan Logs">
                                <span class="sidebar__label">QR Scan Logs</span>
                            </a>
                        </div>
                    </div>

                    <div class="nav-subgroup {{ $employeeMgmtActive ? 'is-route-active' : '' }}" data-nav-group="dtr-employees">
                        <button type="button" class="nav-subgroup__trigger" data-nav-trigger aria-expanded="{{ $employeeMgmtActive ? 'true' : 'false' }}">
                            <span class="nav-subgroup__label">Employee Management</span>
                            <svg class="nav-group__chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div class="nav-subgroup__panel" data-nav-panel>
                            <a href="{{ route('employees.index') }}" class="sidebar__link sidebar__link--nested {{ request()->routeIs('employees.*') ? 'is-active' : '' }}" data-tooltip="Employees">
                                <span class="sidebar__label">Employees</span>
                            </a>
                            <a href="{{ route('attendance.schedules.index') }}" class="sidebar__link sidebar__link--nested {{ request()->routeIs('attendance.schedules.*') || request()->routeIs('attendance.shifts.*') ? 'is-active' : '' }}" data-nav-paths="/attendance/schedules*,/attendance/shifts*" data-tooltip="Employee Schedules">
                                <span class="sidebar__label">Employee Schedules</span>
                            </a>
                            @if($isAdmin)
                                <a href="{{ route('attendance.corrections.index') }}" class="sidebar__link sidebar__link--nested {{ request()->routeIs('attendance.corrections.*') || request()->routeIs('attendance.correction-requests.*') ? 'is-active' : '' }}" data-nav-paths="/attendance/corrections*,/attendance/correction-requests*" data-tooltip="DTR Corrections">
                                    <span class="sidebar__label">DTR Corrections</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="nav-subgroup {{ $attendanceReportsActive ? 'is-route-active' : '' }}" data-nav-group="dtr-reports">
                        <button type="button" class="nav-subgroup__trigger" data-nav-trigger aria-expanded="{{ $attendanceReportsActive ? 'true' : 'false' }}">
                            <span class="nav-subgroup__label">Reports &amp; Settings</span>
                            <svg class="nav-group__chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div class="nav-subgroup__panel" data-nav-panel>
                            <a href="{{ route('attendance.reports.index') }}" class="sidebar__link sidebar__link--nested {{ request()->routeIs('attendance.reports.*') ? 'is-active' : '' }}" data-tooltip="Attendance Reports">
                                <span class="sidebar__label">Attendance Reports</span>
                            </a>
                            @if($isAdmin)
                                <a href="{{ route('attendance.settings.edit') }}" class="sidebar__link sidebar__link--nested {{ request()->routeIs('attendance.settings*') ? 'is-active' : '' }}" data-tooltip="Attendance Settings">
                                    <span class="sidebar__label">Attendance Settings</span>
                                </a>
                                <a href="{{ route('attendance.audit-logs') }}" class="sidebar__link sidebar__link--nested {{ request()->routeIs('attendance.audit-logs') ? 'is-active' : '' }}" data-tooltip="Attendance Audit Logs">
                                    <span class="sidebar__label">Attendance Audit Logs</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Master Data --}}
        <div class="nav-group {{ $masterDataActive ? 'is-route-active' : '' }}" data-nav-group="master-data">
            <button type="button" class="nav-group__trigger" data-nav-trigger aria-expanded="{{ $masterDataActive ? 'true' : 'false' }}">
                <span class="nav-group__label">Master Data</span>
                <svg class="nav-group__chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="nav-group__panel" data-nav-panel>
                <a href="{{ route('categories.index') }}" class="sidebar__link {{ request()->routeIs('categories.*') ? 'is-active' : '' }}" data-tooltip="Categories">
                    <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
                    <span class="sidebar__label">Categories</span>
                </a>
                <a href="{{ route('locations.index') }}" class="sidebar__link {{ request()->routeIs('locations.*') ? 'is-active' : '' }}" data-tooltip="Locations">
                    <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="sidebar__label">Locations</span>
                </a>
                <a href="{{ route('departments.index') }}" class="sidebar__link {{ request()->routeIs('departments.*') ? 'is-active' : '' }}" data-tooltip="Departments">
                    <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span class="sidebar__label">Departments</span>
                </a>
                <a href="{{ route('suppliers.index') }}" class="sidebar__link {{ request()->routeIs('suppliers.*') ? 'is-active' : '' }}" data-tooltip="Suppliers">
                    <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    <span class="sidebar__label">Suppliers</span>
                </a>
            </div>
        </div>

        {{-- Insights --}}
        <div class="nav-group {{ $insightsActive ? 'is-route-active' : '' }}" data-nav-group="insights">
            <button type="button" class="nav-group__trigger" data-nav-trigger aria-expanded="{{ $insightsActive ? 'true' : 'false' }}">
                <span class="nav-group__label">Insights</span>
                <svg class="nav-group__chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="nav-group__panel" data-nav-panel>
                <a href="{{ route('reports.index') }}" class="sidebar__link {{ request()->routeIs('reports.*') ? 'is-active' : '' }}" data-tooltip="Reports">
                    <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span class="sidebar__label">Reports</span>
                </a>
                <a href="{{ route('notifications.index') }}" class="sidebar__link {{ request()->routeIs('notifications.*') ? 'is-active' : '' }}" data-tooltip="Notifications">
                    <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span class="sidebar__label">Notifications</span>
                </a>
            </div>
        </div>

        @if($isAdmin)
            {{-- Administration --}}
            <div class="nav-group {{ $adminActive ? 'is-route-active' : '' }}" data-nav-group="administration">
                <button type="button" class="nav-group__trigger" data-nav-trigger aria-expanded="{{ $adminActive ? 'true' : 'false' }}">
                    <span class="nav-group__label">Administration</span>
                    <svg class="nav-group__chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="nav-group__panel" data-nav-panel>
                    <a href="{{ route('users.index') }}" class="sidebar__link {{ request()->routeIs('users.*') ? 'is-active' : '' }}" data-tooltip="Users">
                        <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span class="sidebar__label">Users</span>
                    </a>
                    <a href="{{ route('settings.edit') }}" class="sidebar__link {{ request()->routeIs('settings.*') ? 'is-active' : '' }}" data-tooltip="Settings">
                        <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="sidebar__label">Settings</span>
                    </a>
                    <a href="{{ route('audit.index') }}" class="sidebar__link {{ request()->routeIs('audit.*') ? 'is-active' : '' }}" data-tooltip="Audit Logs">
                        <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span class="sidebar__label">Audit Logs</span>
                    </a>
                    <a href="{{ route('import.index') }}" class="sidebar__link {{ request()->routeIs('import.*') ? 'is-active' : '' }}" data-tooltip="Import">
                        <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        <span class="sidebar__label">Import</span>
                    </a>
                    <a href="{{ route('export.index') }}" class="sidebar__link {{ request()->routeIs('export.*') ? 'is-active' : '' }}" data-tooltip="Export">
                        <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span class="sidebar__label">Export</span>
                    </a>
                    <a href="{{ route('backups.index') }}" class="sidebar__link {{ request()->routeIs('backups.*') ? 'is-active' : '' }}" data-tooltip="Backups">
                        <svg class="sidebar__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        <span class="sidebar__label">Backups</span>
                    </a>
                </div>
            </div>
        @endif
    </nav>

    <div class="sidebar__user">
        <span class="sidebar__user-avatar" aria-hidden="true">{{ $userInitials }}</span>
        <span class="sidebar__user-info">
            <span class="sidebar__user-name" title="{{ $displayName }}">{{ $displayName }}</span>
            <span class="sidebar__user-role">{{ $userRole }}</span>
        </span>
    </div>
</aside>

<div class="app-main" id="app-main">
    <header class="topbar no-print">
        <div class="topbar__start">
            <button type="button" class="topbar__toggle" id="sidebar-toggle" aria-label="Open navigation menu" aria-controls="app-sidebar" aria-expanded="false">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <button type="button" class="topbar__search-toggle" id="search-toggle" aria-label="Open search" aria-expanded="false">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </button>
        </div>

        <div class="topbar__search" id="topbar-search">
            <svg class="topbar__search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="search" id="global-search" placeholder="Search inventory, products, or item codes…" autocomplete="off"
                   data-search-url="{{ route('inventory.index') }}"
                   value="{{ request('search') }}"
                   aria-label="Global search">
            <kbd class="topbar__search-kbd" aria-hidden="true">Ctrl K</kbd>
        </div>

        <div class="topbar__actions">
            <a href="{{ route('notifications.index') }}" class="topbar__btn topbar__btn--notify" id="notification-btn" aria-label="Notifications">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <span class="topbar__badge" id="notification-badge" data-poll-url="{{ route('notifications.unread') }}">0</span>
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
                            <span class="account-menu__role">{{ $userRole }}</span>
                        </div>
                    </div>
                    @if($isAdmin)
                        <a href="{{ route('settings.edit') }}" class="account-menu__item" role="menuitem">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Account Settings
                        </a>
                    @endif
                    @include('partials.pwa-install-button', ['showIcon' => true])
                    <form action="{{ route('logout') }}" method="post" class="account-menu__logout logout-form">
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

    <main class="page-content" id="page-content">
        @include('partials.flash')
        @include('partials.alerts')
        @yield('content')
    </main>
</div>

<div id="toast-container" class="toast-container" aria-live="polite"></div>

@include('partials.logout-confirmation-modal')

<script src="{{ asset('js/app.js') }}"></script>
<script src="{{ asset('js/navigation.js') }}"></script>
<script src="{{ asset('js/pwa.js') }}"></script>
@stack('scripts')
</body>
</html>
@endif
