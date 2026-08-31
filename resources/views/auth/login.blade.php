@extends('layouts.landing')

@section('title', 'Home')
@section('meta_description', 'QR Inventory System — manage products, stock, QR codes, and inventory transactions in one place.')
@section('body_class', ($errors->has('email') || $errors->has('password')) ? 'has-login-errors' : (($errors->has('station_code') || $errors->has('station_password')) ? 'has-station-errors' : ''))

@section('content')
@php
    $orgName = setting('organization_name', 'QR Inventory System');
@endphp

{{-- Sticky header --}}
<header class="lp-nav" id="lp-nav">
    <div class="lp-container lp-nav__inner">
        <a class="lp-nav__brand" href="#home" aria-label="{{ $orgName }} home">
            <span class="lp-nav__logo" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M3 3h8v8H3V3zm2 2v4h4V5H5zm8-2h8v8h-8V3zm2 2v4h4V5h-4zM3 13h8v8H3v-8zm2 2v4h4v-4H5zm10-2h2v2h-2v-2zm4 0h2v2h-2v-2zm-4 4h2v2h-2v-2zm2 2h2v2h-2v-2zm2-2h2v2h-2v-2zm0 4h2v2h-2v-2z"/>
                </svg>
            </span>
            <span class="lp-nav__name">{{ $orgName }}</span>
        </a>

        <button
            type="button"
            class="lp-nav__toggle"
            id="lp-nav-toggle"
            aria-controls="lp-nav-panel"
            aria-expanded="false"
            aria-label="Open menu"
        >
            <span class="lp-nav__toggle-bars" aria-hidden="true">
                <span></span><span></span><span></span>
            </span>
        </button>

        <div class="lp-nav__panel" id="lp-nav-panel">
            <nav class="lp-nav__desktop" aria-label="Primary">
                <ul class="lp-nav__links" id="lp-nav-links">
                    <li><a href="#home" data-nav-section="home">Home</a></li>
                    <li><a href="#about" data-nav-section="about">About System</a></li>
                    <li><a href="#features" data-nav-section="features">Features</a></li>
                    <li><a href="#how-it-works" data-nav-section="how-it-works">How It Works</a></li>
                    <li><a href="#employee-portal" data-nav-section="employee-portal">For Employees</a></li>
                    <li><a href="#qr-station" data-nav-section="qr-station">QR Station</a></li>
                </ul>
            </nav>

            <div class="lp-nav__actions lp-nav__desktop">
                @if (Route::has('employee.login'))
                    <a class="lp-nav__employee" href="{{ route('employee.login') }}">
                        <i class="fa-solid fa-user" aria-hidden="true"></i>
                        Employee Portal
                    </a>
                @endif
                <a class="lp-nav__employee lp-nav__employee--station" href="#qr-station">
                    <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
                    QR Station
                </a>
                <a class="lp-btn lp-btn--primary lp-btn--sm" href="#login" data-focus-login>Sign In</a>
            </div>

            <div class="lp-nav__panel-actions">
                <ul class="lp-nav__links">
                    <li><a href="#home" data-nav-section="home">Home</a></li>
                    <li><a href="#about" data-nav-section="about">About System</a></li>
                    <li><a href="#features" data-nav-section="features">Features</a></li>
                    <li><a href="#how-it-works" data-nav-section="how-it-works">How It Works</a></li>
                    <li><a href="#employee-portal" data-nav-section="employee-portal">For Employees</a></li>
                    <li><a href="#qr-station" data-nav-section="qr-station">QR Station</a></li>
                </ul>
                @if (Route::has('employee.login'))
                    <a class="lp-nav__employee" href="{{ route('employee.login') }}">
                        <i class="fa-solid fa-user" aria-hidden="true"></i>
                        Employee Portal
                    </a>
                @endif
                <a class="lp-nav__employee lp-nav__employee--station" href="#qr-station">
                    <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
                    QR Station
                </a>
                <a class="lp-btn lp-btn--primary" href="#login" data-focus-login>Sign In</a>
            </div>
        </div>
    </div>
</header>

<main id="main-content">
    {{-- Global session alerts --}}
    @if (session('success') && ! $errors->any())
        <div class="lp-container lp-alert-banner">
            <div class="lp-alert lp-alert--success" role="status">
                <span class="lp-alert__icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                <div class="lp-alert__body">{{ session('success') }}</div>
            </div>
        </div>
    @endif
    @if (session('error') && ! $errors->any())
        <div class="lp-container lp-alert-banner">
            <div class="lp-alert" role="alert">
                <span class="lp-alert__icon" aria-hidden="true"><i class="fa-solid fa-circle-exclamation"></i></span>
                <div class="lp-alert__body">{{ session('error') }}</div>
            </div>
        </div>
    @endif

    {{-- Hero --}}
    <section class="lp-hero" id="home" aria-labelledby="hero-title">
        <div class="lp-container lp-hero__grid">
            <div class="lp-reveal">
                <span class="lp-badge">
                    <i class="fa-solid fa-bolt" aria-hidden="true"></i>
                    Smart Inventory &amp; Attendance
                </span>
                <h1 class="lp-hero__title" id="hero-title">Manage Inventory and Attendance Smarter with QR Technology</h1>
                <p class="lp-hero__text">
                    A fast, reliable platform for QR-based inventory tracking, stock monitoring, and employee attendance — all in one organized system.
                </p>
                <div class="lp-hero__actions">
                    <a class="lp-btn lp-btn--primary lp-btn--lg" href="#login" data-focus-login>
                        <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
                        Sign In
                    </a>
                    @if (Route::has('employee.login'))
                        <a class="lp-btn lp-btn--secondary" href="{{ route('employee.login') }}">
                            <i class="fa-solid fa-user" aria-hidden="true"></i>
                            Employee Portal
                        </a>
                    @endif
                    <a class="lp-btn lp-btn--ghost" href="#qr-station">
                        <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
                        QR Attendance Station
                    </a>
                </div>
            </div>

            <aside class="lp-preview lp-preview--dashboard lp-reveal" aria-label="System dashboard preview">
                <div class="lp-preview__bar">
                    <span class="lp-preview__dot lp-preview__dot--red" aria-hidden="true"></span>
                    <span class="lp-preview__dot lp-preview__dot--yellow" aria-hidden="true"></span>
                    <span class="lp-preview__dot lp-preview__dot--green" aria-hidden="true"></span>
                    <span class="lp-preview__label">Dashboard Overview</span>
                    <span class="lp-preview__live" aria-hidden="true"><span class="lp-preview__live-dot"></span> Live</span>
                </div>
                <div class="lp-preview__body">
                    <div class="lp-preview__stats">
                        <div class="lp-preview__stat">
                            <div class="lp-preview__stat-head">
                                <span class="lp-preview__stat-icon lp-preview__stat-icon--blue" aria-hidden="true"><i class="fa-solid fa-box"></i></span>
                                <span class="lp-preview__stat-label">Products</span>
                            </div>
                            <div class="lp-preview__stat-value">{{ number_format($landingStats['products']) }}</div>
                        </div>
                        <div class="lp-preview__stat">
                            <div class="lp-preview__stat-head">
                                <span class="lp-preview__stat-icon lp-preview__stat-icon--green" aria-hidden="true"><i class="fa-solid fa-warehouse"></i></span>
                                <span class="lp-preview__stat-label">Available Stock</span>
                            </div>
                            <div class="lp-preview__stat-value">{{ number_format($landingStats['stock'], $landingStats['stock'] == floor($landingStats['stock']) ? 0 : 2) }}</div>
                        </div>
                        <div class="lp-preview__stat{{ $landingStats['low_stock'] > 0 ? ' lp-preview__stat--warn' : '' }}">
                            <div class="lp-preview__stat-head">
                                <span class="lp-preview__stat-icon lp-preview__stat-icon--amber" aria-hidden="true"><i class="fa-solid fa-triangle-exclamation"></i></span>
                                <span class="lp-preview__stat-label">Low Stock</span>
                            </div>
                            <div class="lp-preview__stat-value">{{ number_format($landingStats['low_stock']) }}</div>
                            @if ($landingStats['low_stock'] > 0)
                                <span class="lp-preview__stat-badge">Needs attention</span>
                            @endif
                        </div>
                        <div class="lp-preview__stat">
                            <div class="lp-preview__stat-head">
                                <span class="lp-preview__stat-icon lp-preview__stat-icon--purple" aria-hidden="true"><i class="fa-solid fa-right-left"></i></span>
                                <span class="lp-preview__stat-label">Transactions</span>
                            </div>
                            <div class="lp-preview__stat-value">{{ number_format($landingStats['transactions']) }}</div>
                        </div>
                    </div>

                    <div class="lp-preview__scan lp-preview__scan--featured">
                        <div class="lp-preview__scan-inner">
                            <div class="lp-preview__qr" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M3 3h8v8H3V3zm2 2v4h4V5H5zm8-2h8v8h-8V3zm2 2v4h4V5h-4zM3 13h8v8H3v-8zm2 2v4h4v-4H5zm10-2h2v2h-2v-2zm4 0h2v2h-2v-2zm-4 4h2v2h-2v-2zm2 2h2v2h-2v-2zm2-2h2v2h-2v-2zm0 4h2v2h-2v-2z"/>
                                </svg>
                            </div>
                            <div class="lp-preview__scan-text">
                                <strong>QR Scanner Active</strong>
                                <span>Scan items or employee codes instantly</span>
                            </div>
                            <span class="lp-preview__scan-status" aria-hidden="true">
                                <i class="fa-solid fa-circle"></i> Ready
                            </span>
                        </div>
                        <div class="lp-preview__scan-bar" aria-hidden="true">
                            <span class="lp-preview__scan-line"></span>
                        </div>
                    </div>

                    <p class="lp-preview__note">Live snapshot from your database</p>
                </div>
            </aside>
        </div>
    </section>

    {{-- Quick Access / User Type --}}
    <section class="lp-section lp-section--compact" id="quick-access" aria-labelledby="quick-access-title">
        <div class="lp-container">
            <header class="lp-section__header lp-section__header--center lp-reveal">
                <p class="lp-section__eyebrow">Get Started</p>
                <h2 class="lp-section__title" id="quick-access-title">Choose how you want to access the system</h2>
                <p class="lp-section__lead">Select the portal that matches your role — admin, employee, or attendance station.</p>
            </header>

            <div class="lp-access">
                <article class="lp-access__card lp-reveal">
                    <div class="lp-access__icon lp-access__icon--primary" aria-hidden="true">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h3 class="lp-access__title">Admin / Staff</h3>
                    <p class="lp-access__desc">Full inventory management access for administrators and staff members.</p>
                    <ul class="lp-access__list">
                        <li><i class="fa-solid fa-check" aria-hidden="true"></i> Manage inventory</li>
                        <li><i class="fa-solid fa-check" aria-hidden="true"></i> Monitor stock</li>
                        <li><i class="fa-solid fa-check" aria-hidden="true"></i> Track transactions</li>
                    </ul>
                    <a class="lp-btn lp-btn--primary" href="#login" data-focus-login>Sign In</a>
                </article>

                <article class="lp-access__card lp-access__card--featured lp-reveal">
                    <div class="lp-access__icon lp-access__icon--employee" aria-hidden="true">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <h3 class="lp-access__title">Employee</h3>
                    <p class="lp-access__desc">Self-service portal for attendance, schedules, and personal records.</p>
                    <ul class="lp-access__list">
                        <li><i class="fa-solid fa-check" aria-hidden="true"></i> View attendance</li>
                        <li><i class="fa-solid fa-check" aria-hidden="true"></i> View DTR</li>
                        <li><i class="fa-solid fa-check" aria-hidden="true"></i> Manage profile</li>
                        <li><i class="fa-solid fa-check" aria-hidden="true"></i> View personal QR code</li>
                    </ul>
                    @if (Route::has('employee.login'))
                        <a class="lp-btn lp-btn--secondary" href="{{ route('employee.login') }}">Employee Portal</a>
                    @else
                        <a class="lp-btn lp-btn--secondary" href="#employee-portal">Learn More</a>
                    @endif
                </article>

                <article class="lp-access__card lp-reveal">
                    <div class="lp-access__icon lp-access__icon--station" aria-hidden="true">
                        <i class="fa-solid fa-tablet-screen-button"></i>
                    </div>
                    <h3 class="lp-access__title">QR Attendance Station</h3>
                    <p class="lp-access__desc">Dedicated kiosk device for scanning employee QR codes at check-in points.</p>
                    <ul class="lp-access__list">
                        <li><i class="fa-solid fa-check" aria-hidden="true"></i> Scan employee QR codes</li>
                        <li><i class="fa-solid fa-check" aria-hidden="true"></i> Record Time In / Time Out</li>
                        <li><i class="fa-solid fa-check" aria-hidden="true"></i> Dedicated station device</li>
                    </ul>
                    <a class="lp-btn lp-btn--ghost" href="#qr-station">Open QR Station</a>
                </article>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="lp-section lp-section--alt" id="features" aria-labelledby="features-title">
        <div class="lp-container">
            <header class="lp-section__header lp-reveal">
                <p class="lp-section__eyebrow">Features</p>
                <h2 class="lp-section__title" id="features-title">Everything You Need to Manage Inventory</h2>
                <p class="lp-section__lead">
                    Essential inventory operations in one centralized platform — products, quantities, transactions, and QR-coded items.
                </p>
            </header>

            <div class="lp-features">
                <article class="lp-feature lp-reveal">
                    <div class="lp-feature__icon" aria-hidden="true"><i class="fa-solid fa-boxes-stacked"></i></div>
                    <h3 class="lp-feature__title">Inventory Management</h3>
                    <p class="lp-feature__text">Manage products, categories, quantities, and stock information from one centralized system.</p>
                </article>
                <article class="lp-feature lp-reveal">
                    <div class="lp-feature__icon" aria-hidden="true"><i class="fa-solid fa-qrcode"></i></div>
                    <h3 class="lp-feature__title">QR Code Management</h3>
                    <p class="lp-feature__text">Assign and scan QR codes to quickly identify inventory items and access their information.</p>
                </article>
                <article class="lp-feature lp-reveal">
                    <div class="lp-feature__icon" aria-hidden="true"><i class="fa-solid fa-chart-simple"></i></div>
                    <h3 class="lp-feature__title">Stock Monitoring</h3>
                    <p class="lp-feature__text">Monitor available quantities and identify low-stock items before they become a problem.</p>
                </article>
                <article class="lp-feature lp-reveal">
                    <div class="lp-feature__icon" aria-hidden="true"><i class="fa-solid fa-right-left"></i></div>
                    <h3 class="lp-feature__title">Transaction Tracking</h3>
                    <p class="lp-feature__text">Keep track of inventory movements and transactions for better accountability.</p>
                </article>
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="lp-section" id="how-it-works" aria-labelledby="how-title">
        <div class="lp-container">
            <header class="lp-section__header lp-section__header--center lp-reveal">
                <p class="lp-section__eyebrow">Process</p>
                <h2 class="lp-section__title" id="how-title">How It Works</h2>
                <p class="lp-section__lead">From registering items to monitoring activity — four simple steps.</p>
            </header>

            <ol class="lp-steps">
                <li class="lp-step lp-reveal">
                    <div class="lp-step__marker">
                        <span class="lp-step__num">01</span>
                        <span class="lp-step__icon" aria-hidden="true"><i class="fa-solid fa-plus"></i></span>
                    </div>
                    <h3 class="lp-step__title">Add Inventory</h3>
                    <p class="lp-step__text">Register products and enter the necessary inventory information.</p>
                </li>
                <li class="lp-step lp-reveal">
                    <div class="lp-step__marker">
                        <span class="lp-step__num">02</span>
                        <span class="lp-step__icon" aria-hidden="true"><i class="fa-solid fa-qrcode"></i></span>
                    </div>
                    <h3 class="lp-step__title">Generate QR Code</h3>
                    <p class="lp-step__text">Assign a unique QR code to each inventory item.</p>
                </li>
                <li class="lp-step lp-reveal">
                    <div class="lp-step__marker">
                        <span class="lp-step__num">03</span>
                        <span class="lp-step__icon" aria-hidden="true"><i class="fa-solid fa-barcode"></i></span>
                    </div>
                    <h3 class="lp-step__title">Scan &amp; Manage</h3>
                    <p class="lp-step__text">Scan the QR code to quickly identify and manage an item.</p>
                </li>
                <li class="lp-step lp-reveal">
                    <div class="lp-step__marker">
                        <span class="lp-step__num">04</span>
                        <span class="lp-step__icon" aria-hidden="true"><i class="fa-solid fa-chart-line"></i></span>
                    </div>
                    <h3 class="lp-step__title">Monitor Inventory</h3>
                    <p class="lp-step__text">Track quantities, stock movements, and inventory activity.</p>
                </li>
            </ol>
        </div>
    </section>

    {{-- Benefits --}}
    <section class="lp-section lp-section--alt" id="benefits" aria-labelledby="benefits-title">
        <div class="lp-container">
            <header class="lp-section__header lp-section__header--center lp-reveal">
                <p class="lp-section__eyebrow">Benefits</p>
                <h2 class="lp-section__title" id="benefits-title">Built for Better Operations</h2>
                <p class="lp-section__lead">Clear, organized, and easier to follow — for inventory and attendance.</p>
            </header>

            <div class="lp-benefits">
                <article class="lp-benefit lp-reveal">
                    <div class="lp-benefit__icon" aria-hidden="true"><i class="fa-solid fa-bolt"></i></div>
                    <h3 class="lp-benefit__title">Faster</h3>
                    <p class="lp-benefit__text">Reduce time spent searching and identifying items or records.</p>
                </article>
                <article class="lp-benefit lp-reveal">
                    <div class="lp-benefit__icon" aria-hidden="true"><i class="fa-solid fa-bullseye"></i></div>
                    <h3 class="lp-benefit__title">More Accurate</h3>
                    <p class="lp-benefit__text">Minimize manual data entry and recording errors.</p>
                </article>
                <article class="lp-benefit lp-reveal">
                    <div class="lp-benefit__icon" aria-hidden="true"><i class="fa-solid fa-folder-tree"></i></div>
                    <h3 class="lp-benefit__title">Organized</h3>
                    <p class="lp-benefit__text">Keep all information centralized and easy to manage.</p>
                </article>
                <article class="lp-benefit lp-reveal">
                    <div class="lp-benefit__icon" aria-hidden="true"><i class="fa-solid fa-route"></i></div>
                    <h3 class="lp-benefit__title">Traceable</h3>
                    <p class="lp-benefit__text">Maintain visibility of transactions and attendance records.</p>
                </article>
            </div>
        </div>
    </section>

    {{-- Employee Portal --}}
    <section class="lp-section" id="employee-portal" aria-labelledby="employee-title">
        <div class="lp-container">
            <header class="lp-section__header lp-reveal">
                <p class="lp-section__eyebrow">Employee Portal</p>
                <h2 class="lp-section__title" id="employee-title">Your Self-Service Employee Portal</h2>
                <p class="lp-section__lead">
                    Employees sign in to a dedicated portal to view attendance, schedules, DTR records, and their personal QR code — separate from the admin dashboard.
                </p>
            </header>

            <div class="lp-employee">
                <div class="lp-employee__features">
                    <article class="lp-feature lp-feature--compact lp-reveal">
                        <div class="lp-feature__icon" aria-hidden="true"><i class="fa-solid fa-clock"></i></div>
                        <h3 class="lp-feature__title">Attendance Overview</h3>
                        <p class="lp-feature__text">Today's time in/out, status, and monthly present, late, and absent summary.</p>
                    </article>
                    <article class="lp-feature lp-feature--compact lp-reveal">
                        <div class="lp-feature__icon" aria-hidden="true"><i class="fa-solid fa-calendar-days"></i></div>
                        <h3 class="lp-feature__title">Schedule &amp; Calendar</h3>
                        <p class="lp-feature__text">Check your assigned schedule and review attendance on a calendar view.</p>
                    </article>
                    <article class="lp-feature lp-feature--compact lp-reveal">
                        <div class="lp-feature__icon" aria-hidden="true"><i class="fa-solid fa-file-lines"></i></div>
                        <h3 class="lp-feature__title">My DTR</h3>
                        <p class="lp-feature__text">View your daily time record and export or print DTR details when needed.</p>
                    </article>
                    <article class="lp-feature lp-feature--compact lp-reveal">
                        <div class="lp-feature__icon" aria-hidden="true"><i class="fa-solid fa-qrcode"></i></div>
                        <h3 class="lp-feature__title">My QR Code</h3>
                        <p class="lp-feature__text">Access your personal attendance QR code for scanning at check-in points.</p>
                    </article>
                    <article class="lp-feature lp-feature--compact lp-reveal">
                        <div class="lp-feature__icon" aria-hidden="true"><i class="fa-solid fa-pen-to-square"></i></div>
                        <h3 class="lp-feature__title">Correction Requests</h3>
                        <p class="lp-feature__text">Submit DTR correction requests when attendance records need review.</p>
                    </article>
                    <article class="lp-feature lp-feature--compact lp-reveal">
                        <div class="lp-feature__icon" aria-hidden="true"><i class="fa-solid fa-bell"></i></div>
                        <h3 class="lp-feature__title">Profile &amp; Alerts</h3>
                        <p class="lp-feature__text">Update your profile, change your password, and stay informed with notifications.</p>
                    </article>
                </div>

                <aside class="lp-employee__overview lp-reveal" aria-label="Employee dashboard preview">
                    <div class="lp-preview lp-preview--employee">
                        <div class="lp-preview__bar">
                            <span class="lp-preview__dot lp-preview__dot--red" aria-hidden="true"></span>
                            <span class="lp-preview__dot lp-preview__dot--yellow" aria-hidden="true"></span>
                            <span class="lp-preview__dot lp-preview__dot--green" aria-hidden="true"></span>
                            <span class="lp-preview__label">Employee Dashboard</span>
                        </div>
                        <div class="lp-preview__body">
                            <div class="lp-employee__today">
                                <div class="lp-employee__today-header">
                                    <p class="lp-employee__today-label">Today's Attendance</p>
                                    <span class="lp-status lp-status--present">Present</span>
                                </div>
                                <ul class="lp-employee__today-list">
                                    <li>
                                        <span><i class="fa-solid fa-calendar" aria-hidden="true"></i> Schedule</span>
                                        <strong>08:00 AM – 05:00 PM</strong>
                                    </li>
                                    <li>
                                        <span><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> Time In</span>
                                        <strong class="lp-employee__time-in">07:58 AM</strong>
                                    </li>
                                    <li>
                                        <span><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> Time Out</span>
                                        <strong class="lp-employee__time-out">—</strong>
                                    </li>
                                    <li>
                                        <span><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Status</span>
                                        <strong>On time</strong>
                                    </li>
                                </ul>
                            </div>

                            <div class="lp-employee__summary">
                                <p class="lp-employee__summary-label">Monthly Summary</p>
                                <div class="lp-preview__stats lp-employee__month">
                                    <div class="lp-preview__stat lp-preview__stat--mini">
                                        <span class="lp-preview__stat-label">Present</span>
                                        <div class="lp-preview__stat-value lp-preview__stat-value--sm">18 days</div>
                                    </div>
                                    <div class="lp-preview__stat lp-preview__stat--mini">
                                        <span class="lp-preview__stat-label">Late</span>
                                        <div class="lp-preview__stat-value lp-preview__stat-value--sm lp-text--warn">2 days</div>
                                    </div>
                                    <div class="lp-preview__stat lp-preview__stat--mini">
                                        <span class="lp-preview__stat-label">Absent</span>
                                        <div class="lp-preview__stat-value lp-preview__stat-value--sm lp-text--danger">0 days</div>
                                    </div>
                                    <div class="lp-preview__stat lp-preview__stat--mini">
                                        <span class="lp-preview__stat-label">Hours</span>
                                        <div class="lp-preview__stat-value lp-preview__stat-value--sm">144 hrs</div>
                                    </div>
                                </div>
                            </div>

                            <p class="lp-preview__note">Preview of what employees see after signing in</p>
                        </div>
                    </div>

                    @if (Route::has('employee.login'))
                        <div class="lp-employee__cta">
                            <a class="lp-btn lp-btn--primary lp-btn--lg" href="{{ route('employee.login') }}">
                                Sign in to Employee Portal
                                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </section>

    {{-- QR Attendance Station --}}
    <section class="lp-section lp-login lp-station" id="qr-station" aria-labelledby="qr-station-title">
        <div class="lp-container lp-login__grid">
            <div class="lp-login__copy lp-reveal">
                <p class="lp-section__eyebrow">Attendance Kiosk</p>
                <h2 class="lp-section__title" id="qr-station-title">QR Attendance Station</h2>
                <p class="lp-section__lead">
                    Use a dedicated device to scan employee QR codes and automatically record Time In and Time Out.
                </p>

                <ol class="lp-station__flow" aria-label="Station workflow">
                    <li class="lp-station__flow-step">
                        <span class="lp-station__flow-icon" aria-hidden="true"><i class="fa-solid fa-right-to-bracket"></i></span>
                        <span class="lp-station__flow-label">Station Login</span>
                    </li>
                    <li class="lp-station__flow-arrow" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></li>
                    <li class="lp-station__flow-step">
                        <span class="lp-station__flow-icon" aria-hidden="true"><i class="fa-solid fa-qrcode"></i></span>
                        <span class="lp-station__flow-label">Scan Employee QR</span>
                    </li>
                    <li class="lp-station__flow-arrow" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></li>
                    <li class="lp-station__flow-step">
                        <span class="lp-station__flow-icon" aria-hidden="true"><i class="fa-solid fa-user-check"></i></span>
                        <span class="lp-station__flow-label">Verify Employee</span>
                    </li>
                    <li class="lp-station__flow-arrow" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></li>
                    <li class="lp-station__flow-step">
                        <span class="lp-station__flow-icon" aria-hidden="true"><i class="fa-solid fa-clock"></i></span>
                        <span class="lp-station__flow-label">Record Attendance</span>
                    </li>
                </ol>

                <div class="lp-station__security">
                    <p class="lp-station__security-title"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Secure Kiosk Access</p>
                    <ul class="lp-login__checks">
                        <li><i class="fa-solid fa-check" aria-hidden="true"></i> One device per station</li>
                        <li><i class="fa-solid fa-check" aria-hidden="true"></i> Secure station credentials</li>
                        <li><i class="fa-solid fa-check" aria-hidden="true"></i> Dedicated QR scanner</li>
                        <li><i class="fa-solid fa-check" aria-hidden="true"></i> Fast attendance recording</li>
                    </ul>
                </div>
            </div>

            <div class="lp-login__panel lp-station__panel lp-reveal">
                <div class="lp-station__panel-header">
                    <span class="lp-station__panel-icon" aria-hidden="true"><i class="fa-solid fa-tablet-screen-button"></i></span>
                    <div>
                        <h3 class="lp-login__panel-title">Sign in this device</h3>
                        <p class="lp-login__panel-sub">Use your assigned Station ID and password to start scanning employee QR codes.</p>
                    </div>
                </div>

                @if ($errors->has('station_code') || $errors->has('station_password'))
                    <div class="lp-alert" role="alert">
                        <span class="lp-alert__icon" aria-hidden="true"><i class="fa-solid fa-circle-exclamation"></i></span>
                        <div class="lp-alert__body">
                            <strong>Unable to sign in to station</strong>
                            <ul>
                                @foreach ($errors->get('station_code') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                                @foreach ($errors->get('station_password') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @include('auth.partials.station-login-form')
            </div>
        </div>
    </section>

    {{-- About --}}
    <section class="lp-section lp-section--alt" id="about" aria-labelledby="about-title">
        <div class="lp-container lp-about">
            <header class="lp-reveal">
                <p class="lp-section__eyebrow">About System</p>
                <h2 class="lp-section__title" id="about-title">What is the QR Inventory System?</h2>
                <p class="lp-section__lead">
                    A practical platform for organizations that need clear control over products, stock, QR-coded items, and employee attendance self-service.
                </p>
            </header>
            <div class="lp-about__card lp-reveal">
                <p>
                    The QR Inventory System simplifies inventory management by combining product management, QR code identification, stock monitoring, and transaction tracking in one centralized platform. Employees with portal access can also monitor attendance, schedules, and DTR records separately from the inventory dashboard.
                </p>
                <ul class="lp-about__list">
                    <li><i class="fa-solid fa-check" aria-hidden="true"></i><span>Built for staff, admins, and inventory teams</span></li>
                    <li><i class="fa-solid fa-check" aria-hidden="true"></i><span>Supports QR scanning for faster item lookup</span></li>
                    <li><i class="fa-solid fa-check" aria-hidden="true"></i><span>Includes an employee portal for attendance and DTR self-service</span></li>
                </ul>
            </div>
        </div>
    </section>

    {{-- Login --}}
    <section class="lp-section lp-login" id="login" aria-labelledby="login-title">
        <div class="lp-container lp-login__grid">
            <div class="lp-login__copy lp-reveal">
                <p class="lp-section__eyebrow">Access</p>
                <h2 class="lp-section__title" id="login-title">Ready to manage your inventory?</h2>
                <p class="lp-section__lead">
                    Sign in to access your inventory dashboard, manage products, monitor stock, and track inventory activity.
                </p>
                <ul class="lp-login__checks">
                    <li><i class="fa-solid fa-check" aria-hidden="true"></i> Manage inventory</li>
                    <li><i class="fa-solid fa-check" aria-hidden="true"></i> Scan QR codes</li>
                    <li><i class="fa-solid fa-check" aria-hidden="true"></i> Monitor stock</li>
                    <li><i class="fa-solid fa-check" aria-hidden="true"></i> Track transactions</li>
                </ul>
            </div>

            <div class="lp-login__panel lp-reveal">
                <h3 class="lp-login__panel-title">Sign in to the Inventory System</h3>
                <p class="lp-login__panel-sub">Access your inventory management dashboard using your registered account.</p>

                @if (session('success'))
                    <div class="lp-alert lp-alert--success" role="status">
                        <span class="lp-alert__icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                        <div class="lp-alert__body">{{ session('success') }}</div>
                    </div>
                @endif
                @if (session('error'))
                    <div class="lp-alert" role="alert">
                        <span class="lp-alert__icon" aria-hidden="true"><i class="fa-solid fa-circle-exclamation"></i></span>
                        <div class="lp-alert__body">{{ session('error') }}</div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="lp-alert" role="alert">
                        <span class="lp-alert__icon" aria-hidden="true"><i class="fa-solid fa-circle-exclamation"></i></span>
                        <div class="lp-alert__body">
                            <strong>Unable to sign in</strong>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <form method="post" action="{{ route('login') }}" class="lp-login__form" id="login-form" novalidate>
                    @csrf

                    <div class="lp-field">
                        <label class="lp-label" for="email">Email</label>
                        <div class="lp-control @error('email') lp-control--invalid @enderror">
                            <span class="lp-control__icon" aria-hidden="true"><i class="fa-regular fa-envelope"></i></span>
                            <input
                                type="email"
                                name="email"
                                id="email"
                                class="lp-input"
                                value="{{ old('email') }}"
                                placeholder="Enter your email"
                                required
                                autocomplete="username"
                                @error('email') aria-invalid="true" aria-describedby="email-error" @else aria-invalid="false" @enderror
                            >
                        </div>
                        @error('email')
                            <p class="lp-error" id="email-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="lp-field">
                        <label class="lp-label" for="password">Password</label>
                        <div class="lp-control @error('password') lp-control--invalid @enderror">
                            <span class="lp-control__icon" aria-hidden="true"><i class="fa-solid fa-lock"></i></span>
                            <input
                                type="password"
                                name="password"
                                id="password"
                                class="lp-input"
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
                                @error('password') aria-invalid="true" aria-describedby="password-error" @else aria-invalid="false" @enderror
                            >
                            <button
                                type="button"
                                class="lp-toggle"
                                id="toggle-password"
                                aria-label="Show password"
                                aria-controls="password"
                                aria-pressed="false"
                            >
                                <i class="fa-regular fa-eye lp-toggle__show" aria-hidden="true"></i>
                                <i class="fa-regular fa-eye-slash lp-toggle__hide" aria-hidden="true" hidden></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="lp-error" id="password-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="lp-meta">
                        <label class="lp-remember" for="remember">
                            <input
                                type="checkbox"
                                name="remember"
                                id="remember"
                                value="1"
                                {{ old('remember') ? 'checked' : '' }}
                            >
                            <span>Remember me</span>
                        </label>

                        @if (Route::has('employee.password.request'))
                            <a class="lp-forgot" href="{{ route('employee.password.request') }}">Forgot password?</a>
                        @endif
                    </div>

                    <button type="submit" class="lp-submit" id="login-submit">
                        <span class="lp-submit-idle">Sign in</span>
                        <span class="lp-submit-loading" aria-hidden="true">
                            <span class="lp-spinner" aria-hidden="true"></span>
                            Signing in...
                        </span>
                    </button>
                </form>

                @if (Route::has('employee.login'))
                    <p class="lp-employee-link">
                        Are you an employee?
                        <a href="{{ route('employee.login') }}">Sign in to Employee Portal →</a>
                    </p>
                @endif
            </div>
        </div>
    </section>
</main>

<footer class="lp-footer">
    <div class="lp-container">
        <div class="lp-footer__top">
            <a class="lp-footer__brand" href="#home">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M3 3h8v8H3V3zm2 2v4h4V5H5zm8-2h8v8h-8V3zm2 2v4h4V5h-4zM3 13h8v8H3v-8zm2 2v4h4v-4H5zm10-2h2v2h-2v-2zm4 0h2v2h-2v-2zm-4 4h2v2h-2v-2zm2 2h2v2h-2v-2zm2-2h2v2h-2v-2zm0 4h2v2h-2v-2z"/>
                </svg>
                {{ $orgName }}
            </a>
            <nav aria-label="Footer">
                <ul class="lp-footer__links">
                    <li><a href="#home">Home</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#how-it-works">How It Works</a></li>
                    <li><a href="#employee-portal">For Employees</a></li>
                    <li><a href="#login" data-focus-login>Sign In</a></li>
                    @if (Route::has('employee.login'))
                        <li><a href="{{ route('employee.login') }}">Employee Portal</a></li>
                    @endif
                    <li><a href="#qr-station">QR Station</a></li>
                </ul>
            </nav>
        </div>
        <p class="lp-footer__copy">© {{ date('Y') }} {{ $orgName }}. All rights reserved.</p>
    </div>
</footer>
@endsection
