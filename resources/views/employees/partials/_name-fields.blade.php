@php
    $nameEmployee = $employee ?? null;
@endphp

<div class="form-group form-group--name-parts" style="grid-column:1/-1;">
    <div class="form-grid form-grid--3">
        <div class="form-group">
            <label class="form-label" for="last_name">Last name <span class="req">*</span></label>
            <input type="text" name="last_name" id="last_name" class="form-control" value="{{ old('last_name', $nameEmployee?->last_name) }}" required placeholder="Cruz">
            @error('last_name')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="first_name">First name <span class="req">*</span></label>
            <input type="text" name="first_name" id="first_name" class="form-control" value="{{ old('first_name', $nameEmployee?->first_name) }}" required placeholder="Juan">
            @error('first_name')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="middle_name">Middle name</label>
            <input type="text" name="middle_name" id="middle_name" class="form-control" value="{{ old('middle_name', $nameEmployee?->middle_name) }}" placeholder="Dela">
            @error('middle_name')<p class="form-error">{{ $message }}</p>@enderror
        </div>
    </div>
</div>
