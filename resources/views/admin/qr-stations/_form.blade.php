@php
    $isEdit = isset($station) && $station !== null;
    $showExtendedFields = $showExtendedFields ?? $isEdit;
    $prefix = $isEdit ? 'edit_' : '';
    $passwordId = $isEdit ? 'edit_password' : 'create_password';
@endphp

<div class="qs-form-section">
    <h3 class="qs-form-section__title">Station Identity</h3>

    <div class="form-group">
        <label class="form-label" for="{{ $prefix }}station_name">Station Name <span class="text-danger">*</span></label>
        <input type="text" name="station_name" id="{{ $prefix }}station_name" class="form-control @error('station_name') is-invalid @enderror" value="{{ old('station_name', $station?->station_name ?? '') }}" required maxlength="255" placeholder="Main Office">
        @error('station_name')<p class="form-hint text-danger">{{ $message }}</p>@enderror
    </div>

    <div class="form-group mb-0">
        <label class="form-label" for="{{ $prefix }}station_code">Station ID <span class="text-danger">*</span></label>
        <input type="text" name="station_code" id="{{ $prefix }}station_code" class="form-control @error('station_code') is-invalid @enderror" value="{{ old('station_code', $station?->station_code ?? '') }}" required maxlength="100" placeholder="STATION-001" data-qs-uppercase autocomplete="off">
        <p class="form-hint">Unique identifier used when logging into this station. Example: <code>STATION-001</code></p>
        @error('station_code')<p class="form-hint text-danger">{{ $message }}</p>@enderror
    </div>
</div>

<div class="qs-form-section">
    <h3 class="qs-form-section__title">Location</h3>

    <div class="form-group">
        <label class="form-label" for="{{ $prefix }}location">Location <span class="text-danger">*</span></label>
        <input type="text" name="location" id="{{ $prefix }}location" class="form-control @error('location') is-invalid @enderror" value="{{ old('location', $station?->location ?? '') }}" required maxlength="255" placeholder="Building A, Ground Floor">
        @error('location')<p class="form-hint text-danger">{{ $message }}</p>@enderror
    </div>

    @if($showExtendedFields)
        <div class="qs-form-row">
            <div class="form-group">
                <label class="form-label" for="{{ $prefix }}building">Building</label>
                <input type="text" name="building" id="{{ $prefix }}building" class="form-control" value="{{ old('building', $station?->building ?? '') }}" maxlength="255">
            </div>
            <div class="form-group">
                <label class="form-label" for="{{ $prefix }}department">Department</label>
                <input type="text" name="department" id="{{ $prefix }}department" class="form-control" value="{{ old('department', $station?->department ?? '') }}" maxlength="255" list="department-suggestions">
            </div>
        </div>

        <div class="form-group mb-0">
            <label class="form-label" for="{{ $prefix }}floor_area">Floor / Area</label>
            <input type="text" name="floor_area" id="{{ $prefix }}floor_area" class="form-control" value="{{ old('floor_area', $station?->floor_area ?? '') }}" maxlength="255" placeholder="Ground Floor · Reception">
        </div>
    @endif
</div>

<div class="qs-form-section">
    <h3 class="qs-form-section__title">Security</h3>

    <div class="form-group">
        <label class="form-label" for="{{ $passwordId }}">Station Password @if(!$isEdit)<span class="text-danger">*</span>@endif</label>
        <div class="qs-pw-field">
            <input type="password" name="password" id="{{ $passwordId }}" class="form-control @error('password') is-invalid @enderror" {{ $isEdit ? '' : 'required' }} minlength="8" maxlength="128" autocomplete="new-password" aria-describedby="{{ $passwordId }}_hint">
            <div class="qs-pw-actions">
                <button type="button" class="btn btn--ghost btn--sm js-qs-toggle-password" data-target="{{ $passwordId }}" aria-label="Show password">Show</button>
                <button type="button" class="btn btn--secondary btn--sm js-qs-generate-password" data-target="{{ $passwordId }}" data-generate-url="{{ route('admin.qr-stations.generate-password') }}">Generate</button>
            </div>
        </div>
        <p class="form-hint" id="{{ $passwordId }}_hint">Minimum 8 characters. Used when a device logs into this station.</p>
        @error('password')<p class="form-hint text-danger">{{ $message }}</p>@enderror
        @if($isEdit)
            <p class="form-hint">Leave blank to keep the current password. Changing the password affects future station logins only.</p>
        @endif
    </div>

    <div class="form-group mb-0">
        <label class="form-label" for="{{ $prefix }}status">Status</label>
        <select name="status" id="{{ $prefix }}status" class="form-select">
            <option value="active" @selected(old('status', $station?->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $station?->status ?? 'active') === 'inactive')>Inactive</option>
        </select>
        <p class="form-hint">Inactive stations cannot be used for scanning until reactivated.</p>
    </div>
</div>

@if($showExtendedFields)
<div class="qs-form-section">
    <h3 class="qs-form-section__title">Settings</h3>

    <div class="form-group mb-0">
        <label class="form-label" for="{{ $prefix }}timezone">Timezone</label>
        <input type="text" name="timezone" id="{{ $prefix }}timezone" class="form-control" value="{{ old('timezone', $station?->timezone ?? 'Asia/Manila') }}" maxlength="64">
    </div>
</div>
@endif

<div class="qs-form-section">
    <h3 class="qs-form-section__title">Notes</h3>

    <div class="form-group mb-0">
        <label class="form-label" for="{{ $prefix }}description">Description / Notes</label>
        <textarea name="description" id="{{ $prefix }}description" class="form-control" rows="3" maxlength="2000" placeholder="Optional notes about this station">{{ old('description', $station?->description ?? '') }}</textarea>
    </div>
</div>

@if($showExtendedFields)
<datalist id="department-suggestions">
    @foreach(($departments ?? collect()) as $dept)
        <option value="{{ $dept }}">
    @endforeach
</datalist>
@endif
