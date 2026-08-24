@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div class="page-header"><div><h1>System settings</h1><p class="page-header__meta">Organization and inventory defaults</p></div></div>

<div class="card" style="max-width:720px;">
    <div class="card__body">
        <form method="post" action="{{ route('settings.update') }}">@csrf @method('PUT')
            <div class="form-grid form-grid--1">
                <div class="form-group"><label class="form-label" for="organization_name">Organization name</label>
                    <input type="text" name="organization_name" id="organization_name" class="form-control" value="{{ old('organization_name', $settings['organization_name']) }}" required></div>
                <div class="form-group"><label class="form-label" for="code_prefix">Item code prefix</label>
                    <input type="text" name="code_prefix" id="code_prefix" class="form-control" value="{{ old('code_prefix', $settings['code_prefix']) }}" required></div>
                <div class="form-group"><label class="form-label" for="qr_format">QR download format</label>
                    <select name="qr_format" id="qr_format" class="form-select">
                        <option value="svg" @selected(old('qr_format', $settings['qr_format'])==='svg')>SVG</option>
                        <option value="png" @selected(old('qr_format', $settings['qr_format'])==='png')>PNG</option>
                    </select></div>
                <div class="form-group"><label class="form-label" for="default_min_stock">Default minimum stock</label>
                    <input type="number" step="0.01" min="0" name="default_min_stock" id="default_min_stock" class="form-control" value="{{ old('default_min_stock', $settings['default_min_stock']) }}" required></div>
                <div class="form-group"><label class="form-label" for="currency">Currency code</label>
                    <input type="text" name="currency" id="currency" class="form-control" value="{{ old('currency', $settings['currency']) }}" required></div>
                <div class="form-group"><label class="form-label" for="date_format">Date format</label>
                    <input type="text" name="date_format" id="date_format" class="form-control" value="{{ old('date_format', $settings['date_format']) }}" required></div>
                <div class="form-group"><label class="form-label" for="timezone">Timezone</label>
                    <input type="text" name="timezone" id="timezone" class="form-control" value="{{ old('timezone', $settings['timezone']) }}" required></div>
                <div class="form-group"><label class="form-label" for="session_timeout">Session timeout (minutes)</label>
                    <input type="number" min="0" max="1440" name="session_timeout" id="session_timeout" class="form-control" value="{{ old('session_timeout', $settings['session_timeout']) }}" required></div>
                <div class="form-check"><input type="checkbox" name="notifications_enabled" id="notifications_enabled" value="1" @checked(old('notifications_enabled', $settings['notifications_enabled'])==='1')><label for="notifications_enabled">Enable notifications</label></div>
            </div>
            <button type="submit" class="btn btn--primary mt-2">Save settings</button>
        </form>
    </div>
</div>

<div class="card" style="max-width:720px;" id="pwa-settings-card">
    <div class="card__body">
        <h2 class="card__title" style="margin-top:0;">Install Application</h2>
        <p class="text-muted" style="margin-bottom:1rem;">Install this system on your device for faster access and a native app-like experience.</p>

        <div class="pwa-settings-status" data-pwa-status>
            <span class="pwa-settings-status__dot" aria-hidden="true"></span>
            <span data-pwa-status-label>Browser Version</span>
        </div>

        <div class="pwa-settings-actions mt-2">
            <button type="button" class="btn btn--primary" data-pwa-install hidden>Install App</button>
            <p class="text-muted pwa-settings-hint" data-pwa-installed-only hidden style="margin:0;">
                This device is running the installed application.
            </p>
            <p class="text-muted pwa-settings-hint" data-pwa-browser-only style="margin:0;font-size:.875rem;">
                Installation is available in Chrome, Edge, and other supported browsers. On iPhone or iPad, use Safari's Share menu and choose "Add to Home Screen".
            </p>
        </div>
    </div>
</div>
@endsection
