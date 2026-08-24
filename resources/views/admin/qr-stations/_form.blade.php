@php
    $isEdit = isset($station) && $station !== null;
    $showExtendedFields = $showExtendedFields ?? $isEdit;
@endphp

<div class="form-group">
    <label class="form-label" for="{{ $isEdit ? 'edit_' : '' }}station_name">Station Name <span class="text-danger">*</span></label>
    <input type="text" name="station_name" id="{{ $isEdit ? 'edit_' : '' }}station_name" class="form-control" value="{{ old('station_name', $station?->station_name ?? '') }}" required maxlength="255" placeholder="Branch">
</div>

<div class="form-group">
    <label class="form-label" for="{{ $isEdit ? 'edit_' : '' }}station_code">Station ID <span class="text-danger">*</span></label>
    <input type="text" name="station_code" id="{{ $isEdit ? 'edit_' : '' }}station_code" class="form-control" value="{{ old('station_code', $station?->station_code ?? '') }}" required maxlength="100" placeholder="STATION-001" style="text-transform:uppercase">
    <p class="form-hint">Must be unique. Stored in uppercase.</p>
</div>

<div class="form-group">
    <label class="form-label" for="{{ $isEdit ? 'edit_' : '' }}password">Station Password @if(!$isEdit)<span class="text-danger">*</span>@endif</label>
    <div class="pw-field">
        <input type="password" name="password" id="{{ $isEdit ? 'edit_password' : 'create_password' }}" class="form-control" {{ $isEdit ? '' : 'required' }} minlength="8" maxlength="128" autocomplete="new-password">
        <button type="button" class="btn btn--secondary btn--sm js-generate-password" data-target="{{ $isEdit ? 'edit_password' : 'create_password' }}">Generate</button>
        <button type="button" class="btn btn--ghost btn--sm js-toggle-password" data-target="{{ $isEdit ? 'edit_password' : 'create_password' }}" aria-label="Toggle password visibility">Show</button>
    </div>
    @if($isEdit)
        <p class="form-hint">Leave blank to keep the current password.</p>
    @endif
</div>

<div class="form-group">
    <label class="form-label" for="{{ $isEdit ? 'edit_' : '' }}location">Location <span class="text-danger">*</span></label>
    <input type="text" name="location" id="{{ $isEdit ? 'edit_' : '' }}location" class="form-control" value="{{ old('location', $station?->location ?? '') }}" required maxlength="255" placeholder="Branch">
</div>

<div class="form-group">
    <label class="form-label" for="{{ $isEdit ? 'edit_' : '' }}description">Description / Notes</label>
    <textarea name="description" id="{{ $isEdit ? 'edit_' : '' }}description" class="form-control" rows="2" maxlength="2000">{{ old('description', $station?->description ?? '') }}</textarea>
</div>

@if($showExtendedFields)
<div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem">
    <div class="form-group">
        <label class="form-label" for="{{ $isEdit ? 'edit_' : '' }}building">Assigned Building</label>
        <input type="text" name="building" id="{{ $isEdit ? 'edit_' : '' }}building" class="form-control" value="{{ old('building', $station?->building ?? '') }}" maxlength="255">
    </div>
    <div class="form-group">
        <label class="form-label" for="{{ $isEdit ? 'edit_' : '' }}department">Department</label>
        <input type="text" name="department" id="{{ $isEdit ? 'edit_' : '' }}department" class="form-control" value="{{ old('department', $station?->department ?? '') }}" maxlength="255" list="department-suggestions">
    </div>
</div>

<div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem">
    <div class="form-group">
        <label class="form-label" for="{{ $isEdit ? 'edit_' : '' }}floor_area">Floor / Area</label>
        <input type="text" name="floor_area" id="{{ $isEdit ? 'edit_' : '' }}floor_area" class="form-control" value="{{ old('floor_area', $station?->floor_area ?? '') }}" maxlength="255">
    </div>
    <div class="form-group">
        <label class="form-label" for="{{ $isEdit ? 'edit_' : '' }}timezone">Timezone</label>
        <input type="text" name="timezone" id="{{ $isEdit ? 'edit_' : '' }}timezone" class="form-control" value="{{ old('timezone', $station?->timezone ?? 'Asia/Manila') }}" maxlength="64">
    </div>
</div>
@endif

<div class="form-group">
    <label class="form-label" for="{{ $isEdit ? 'edit_' : '' }}status">Status</label>
    <select name="status" id="{{ $isEdit ? 'edit_' : '' }}status" class="form-select">
        <option value="active" @selected(old('status', $station?->status ?? 'active') === 'active')>Active</option>
        <option value="inactive" @selected(old('status', $station?->status ?? 'active') === 'inactive')>Inactive</option>
    </select>
</div>

@if($showExtendedFields)
<datalist id="department-suggestions">
    @foreach(($departments ?? collect()) as $dept)
        <option value="{{ $dept }}">
    @endforeach
</datalist>
@endif
