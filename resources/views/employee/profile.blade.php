@extends('layouts.employee')

@section('title', 'My Profile')

@php
    $displayName = $user->displayName();
    $nameParts = preg_split('/\s+/', trim($displayName), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if (count($nameParts) > 1) {
        $initials = strtoupper(substr($nameParts[0], 0, 1).substr($nameParts[array_key_last($nameParts)], 0, 1));
    } else {
        $initials = strtoupper(substr($nameParts[0] ?? 'E', 0, 2));
    }

    $canEditPhone = in_array('phone', $editable, true);
    $canEditPhoto = in_array('profile_picture', $editable, true);
    $canEditAny = $canEditPhone || $canEditPhoto;
    $statusClass = $user->status === 'active' ? 'badge--available' : 'badge--archived';
    $profilePhotoUrl = $user->profile_picture ? asset('storage/'.$user->profile_picture) : null;
@endphp

@section('content')
<div class="emp-profile">
    <div class="page-header emp-profile__header">
        <div class="emp-profile__title-row">
            <span class="emp-profile__title-icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </span>
            <div>
                <h1>My Profile</h1>
                <p class="page-header__meta">View and manage your personal account information.</p>
            </div>
        </div>
    </div>

    <div class="emp-profile__layout">
        <div class="emp-profile__main">
            <section class="card emp-profile-summary" aria-labelledby="profile-summary-heading">
                <div class="card__body emp-profile-summary__body">
                    <div class="emp-profile-avatar">
                        @if($profilePhotoUrl)
                            <img src="{{ $profilePhotoUrl }}" alt="{{ $displayName }}">
                        @else
                            <div class="emp-profile-avatar__fallback" aria-hidden="true">{{ $initials }}</div>
                        @endif
                    </div>
                    <h2 class="emp-profile-summary__name" id="profile-summary-heading">{{ $displayName }}</h2>
                    <p class="emp-profile-summary__id">{{ $user->employee_id }}</p>
                    @if($user->department || $user->position)
                        <div class="emp-profile-summary__meta">
                            @if($user->department)
                                <span class="badge badge--default">{{ $user->department }}</span>
                            @endif
                            @if($user->position)
                                <span class="badge emp-profile-summary__badge">{{ $user->position }}</span>
                            @endif
                        </div>
                    @endif
                </div>
            </section>

            <section class="card emp-profile-info" aria-labelledby="profile-info-heading">
                <div class="card__header">
                    <h2 class="card__title" id="profile-info-heading">Personal Information</h2>
                </div>
                <div class="card__body">
                    <dl class="emp-profile-info__grid">
                        <div class="emp-profile-field">
                            <span class="emp-profile-field__icon" aria-hidden="true">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </span>
                            <div>
                                <dt>Full Name</dt>
                                <dd>{{ $displayName }}</dd>
                            </div>
                        </div>
                        <div class="emp-profile-field">
                            <span class="emp-profile-field__icon" aria-hidden="true">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                            </span>
                            <div>
                                <dt>Employee ID</dt>
                                <dd>{{ $user->employee_id }}</dd>
                            </div>
                        </div>
                        <div class="emp-profile-field">
                            <span class="emp-profile-field__icon" aria-hidden="true">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </span>
                            <div>
                                <dt>Email</dt>
                                <dd>{{ $user->email }}</dd>
                            </div>
                        </div>
                        <div class="emp-profile-field">
                            <span class="emp-profile-field__icon" aria-hidden="true">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </span>
                            <div>
                                <dt>Department</dt>
                                <dd>{{ $user->department ?: '—' }}</dd>
                            </div>
                        </div>
                        <div class="emp-profile-field">
                            <span class="emp-profile-field__icon" aria-hidden="true">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </span>
                            <div>
                                <dt>Position</dt>
                                <dd>{{ $user->position ?? '—' }}</dd>
                            </div>
                        </div>
                        <div class="emp-profile-field">
                            <span class="emp-profile-field__icon" aria-hidden="true">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </span>
                            <div>
                                <dt>Employment Status</dt>
                                <dd>
                                    <span class="badge {{ $statusClass }}">{{ ucfirst($user->status) }}</span>
                                </dd>
                            </div>
                        </div>
                        <div class="emp-profile-field">
                            <span class="emp-profile-field__icon" aria-hidden="true">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </span>
                            <div>
                                <dt>Date Hired</dt>
                                <dd>{{ $user->date_hired?->format('M d, Y') ?? '—' }}</dd>
                            </div>
                        </div>
                        <div class="emp-profile-field">
                            <span class="emp-profile-field__icon" aria-hidden="true">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </span>
                            <div>
                                <dt>Contact Number</dt>
                                <dd>{{ $user->phone ?: '—' }}</dd>
                            </div>
                        </div>
                    </dl>
                </div>
            </section>
        </div>

        <aside class="emp-profile__aside">
            <section class="card emp-profile-edit" aria-labelledby="profile-edit-heading">
                <div class="card__header">
                    <div>
                        <h2 class="card__title" id="profile-edit-heading">Edit Profile</h2>
                        <p class="emp-profile-edit__intro">Update the information that has been made available for editing.</p>
                    </div>
                </div>
                <div class="card__body">
                    @if(! $canEditAny)
                        <div class="emp-profile-locked" role="status">
                            <div class="emp-profile-locked__icon" aria-hidden="true">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </div>
                            <h3>Profile editing is currently unavailable.</h3>
                            <p>Your administrator has not enabled any fields for self-service updates.</p>
                        </div>
                    @else
                        <form
                            method="post"
                            action="{{ route('employee.profile.update') }}"
                            enctype="multipart/form-data"
                            id="employee-profile-form"
                            data-original-phone="{{ $user->phone ?? '' }}"
                            data-max-size="2048"
                        >
                            @csrf
                            @method('PUT')

                            @if($canEditPhone)
                                <div class="form-group">
                                    <label class="form-label" for="phone">Contact Number</label>
                                    <p class="form-hint" id="phone-hint">Enter your active mobile or contact number.</p>
                                    <input
                                        type="tel"
                                        name="phone"
                                        id="phone"
                                        class="form-control @error('phone') is-invalid @enderror"
                                        value="{{ old('phone', $user->phone) }}"
                                        placeholder="e.g. 0917 123 4567"
                                        maxlength="50"
                                        autocomplete="tel"
                                        inputmode="tel"
                                        aria-describedby="phone-hint{{ $errors->has('phone') ? ' phone-error' : '' }}"
                                        @error('phone') aria-invalid="true" @enderror
                                    >
                                    @error('phone')
                                        <p class="form-error" id="phone-error" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif

                            @if($canEditPhoto)
                                <div class="form-group" role="group" aria-labelledby="profile-picture-label">
                                    <span class="form-label" id="profile-picture-label">Profile Photo</span>
                                    <div class="emp-profile-upload">
                                        <button
                                            type="button"
                                            class="emp-profile-upload__preview"
                                            id="profile-preview-trigger"
                                            aria-label="Change profile photo"
                                        >
                                            <span class="emp-profile-avatar">
                                                <img
                                                    id="profile-preview-image"
                                                    @if($profilePhotoUrl) src="{{ $profilePhotoUrl }}" @endif
                                                    alt=""
                                                    @if(! $profilePhotoUrl) hidden @endif
                                                >
                                                <span
                                                    class="emp-profile-avatar__fallback"
                                                    id="profile-preview-fallback"
                                                    aria-hidden="true"
                                                    @if($profilePhotoUrl) hidden @endif
                                                >{{ $initials }}</span>
                                            </span>
                                            <span class="emp-profile-upload__camera" aria-hidden="true">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            </span>
                                        </button>
                                        <div class="emp-profile-upload__meta">
                                            <div class="emp-profile-upload__actions">
                                                <button type="button" class="btn btn--secondary" id="profile-photo-trigger">
                                                    Change Profile Photo
                                                </button>
                                            </div>
                                            <label class="sr-only" for="profile_picture">Upload a new profile photo</label>
                                            <input
                                                type="file"
                                                name="profile_picture"
                                                id="profile_picture"
                                                class="sr-only"
                                                tabindex="-1"
                                                accept="image/png,image/jpeg,image/webp,.png,.jpg,.jpeg,.webp"
                                                aria-describedby="profile-picture-hint{{ $errors->has('profile_picture') ? ' profile-picture-error' : '' }}"
                                                @error('profile_picture') aria-invalid="true" @enderror
                                            >
                                            <p class="form-hint" id="profile-picture-hint">PNG, JPG, or WEBP. Maximum file size 2 MB.</p>
                                            <p class="form-error" id="profile-picture-client-error" hidden role="alert"></p>
                                            @error('profile_picture')
                                                <p class="form-error" id="profile-picture-error" role="alert">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="emp-profile-edit__actions">
                                <p class="emp-profile-edit__dirty" id="profile-unsaved" hidden aria-live="polite">You have unsaved changes</p>
                                <button type="submit" class="btn btn--primary emp-profile-edit__submit" id="profile-save-btn">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span class="emp-profile-edit__submit-text">Save Changes</span>
                                    <span class="emp-profile-edit__submit-busy" hidden>Saving…</span>
                                </button>
                            </div>
                        </form>
                    @endif

                    <div class="emp-profile-note" role="note">
                        <svg class="emp-profile-note__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <strong>Administrator-managed information</strong>
                            <p>Your Employee ID, name, department, position, employment status, and other protected information can only be updated by an authorized administrator.</p>
                        </div>
                    </div>
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('employee-profile-form');
    if (!form) return;

    const phoneInput = document.getElementById('phone');
    const fileInput = document.getElementById('profile_picture');
    const saveBtn = document.getElementById('profile-save-btn');
    const unsaved = document.getElementById('profile-unsaved');
    const previewImage = document.getElementById('profile-preview-image');
    const previewFallback = document.getElementById('profile-preview-fallback');
    const clientError = document.getElementById('profile-picture-client-error');
    const pickTriggers = [
        document.getElementById('profile-photo-trigger'),
        document.getElementById('profile-preview-trigger'),
    ].filter(Boolean);

    const originalPhone = form.getAttribute('data-original-phone') || '';
    const maxKb = parseInt(form.getAttribute('data-max-size') || '2048', 10);
    const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
    const allowedExt = ['png', 'jpg', 'jpeg', 'webp'];
    const originalImageSrc = previewImage && previewImage.getAttribute('src') ? previewImage.getAttribute('src') : '';
    let objectUrl = null;
    let submitting = false;

    function showClientError(message) {
        if (!clientError) return;
        clientError.textContent = message;
        clientError.hidden = !message;
        if (fileInput) {
            fileInput.setAttribute('aria-invalid', message ? 'true' : 'false');
        }
    }

    function resetPreview() {
        if (!previewImage || !previewFallback) return;
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }
        if (originalImageSrc) {
            previewImage.src = originalImageSrc;
            previewImage.hidden = false;
            previewFallback.hidden = true;
        } else {
            previewImage.removeAttribute('src');
            previewImage.hidden = true;
            previewFallback.hidden = false;
        }
    }

    function showPreview(file) {
        if (!previewImage || !previewFallback) return;
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = URL.createObjectURL(file);
        previewImage.src = objectUrl;
        previewImage.hidden = false;
        previewFallback.hidden = true;
    }

    function isAllowedFile(file) {
        const ext = (file.name.split('.').pop() || '').toLowerCase();
        const typeOk = !file.type || allowedTypes.includes(file.type);
        const extOk = allowedExt.includes(ext);
        return typeOk && extOk;
    }

    function hasUnsavedChanges() {
        const phoneChanged = phoneInput ? (phoneInput.value || '') !== originalPhone : false;
        const photoChanged = fileInput && fileInput.files && fileInput.files.length > 0;
        return phoneChanged || photoChanged;
    }

    function syncUnsaved() {
        if (!unsaved) return;
        unsaved.hidden = !hasUnsavedChanges();
    }

    function openPicker() {
        if (fileInput) fileInput.click();
    }

    pickTriggers.forEach((el) => el.addEventListener('click', openPicker));

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            showClientError('');
            const file = fileInput.files && fileInput.files[0];
            if (!file) {
                resetPreview();
                syncUnsaved();
                return;
            }
            if (!isAllowedFile(file)) {
                fileInput.value = '';
                resetPreview();
                showClientError('Please choose a PNG, JPG, or WEBP image.');
                syncUnsaved();
                return;
            }
            if (file.size > maxKb * 1024) {
                fileInput.value = '';
                resetPreview();
                showClientError('The selected image must be 2 MB or smaller.');
                syncUnsaved();
                return;
            }
            showPreview(file);
            syncUnsaved();
        });
    }

    if (phoneInput) {
        phoneInput.addEventListener('input', syncUnsaved);
    }

    form.addEventListener('submit', function (event) {
        if (submitting) {
            event.preventDefault();
            return;
        }

        if (fileInput && fileInput.files && fileInput.files[0]) {
            const file = fileInput.files[0];
            if (!isAllowedFile(file)) {
                event.preventDefault();
                showClientError('Please choose a PNG, JPG, or WEBP image.');
                return;
            }
            if (file.size > maxKb * 1024) {
                event.preventDefault();
                showClientError('The selected image must be 2 MB or smaller.');
                return;
            }
        }

        submitting = true;
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.classList.add('is-loading');
            saveBtn.setAttribute('aria-busy', 'true');
            const label = saveBtn.querySelector('.emp-profile-edit__submit-text');
            const busy = saveBtn.querySelector('.emp-profile-edit__submit-busy');
            const icon = saveBtn.querySelector('svg');
            if (label) label.hidden = true;
            if (icon) icon.hidden = true;
            if (busy) busy.hidden = false;
        }
    });

    syncUnsaved();
})();
</script>
@endpush
