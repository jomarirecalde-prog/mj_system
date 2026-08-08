@extends('layouts.employee')

@section('title', 'My Profile')

@section('content')
<div class="page-header">
    <div>
        <h1>My Profile</h1>
        <p class="page-header__meta">Personal information (sensitive fields are protected)</p>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card__body" style="text-align:center;">
            @if($user->profile_picture)
                <img src="{{ asset('storage/'.$user->profile_picture) }}" alt="" style="width:120px;height:120px;border-radius:50%;object-fit:cover;">
            @else
                <div class="topbar__avatar" style="width:120px;height:120px;font-size:2.5rem;margin:0 auto;">{{ strtoupper(substr($user->displayName(), 0, 1)) }}</div>
            @endif
            <h2 style="margin-top:1rem;">{{ $user->displayName() }}</h2>
            <p class="text-muted">{{ $user->employee_id }}</p>
        </div>
        <div class="card__body">
            <dl class="dl-grid">
                <div class="dl-item"><dt>Full Name</dt><dd>{{ $user->displayName() }}</dd></div>
                <div class="dl-item"><dt>Employee ID</dt><dd>{{ $user->employee_id }}</dd></div>
                <div class="dl-item"><dt>Email</dt><dd>{{ $user->email }}</dd></div>
                <div class="dl-item"><dt>Department</dt><dd>{{ $user->department }}</dd></div>
                <div class="dl-item"><dt>Position</dt><dd>{{ $user->position ?? '—' }}</dd></div>
                <div class="dl-item"><dt>Employment Status</dt><dd>{{ ucfirst($user->status) }}</dd></div>
                <div class="dl-item"><dt>Date Hired</dt><dd>{{ $user->date_hired?->format('M d, Y') ?? '—' }}</dd></div>
                <div class="dl-item"><dt>Contact</dt><dd>{{ $user->phone ?: '—' }}</dd></div>
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card__header"><h2 class="card__title">Update allowed fields</h2></div>
        <div class="card__body">
            @if(count(array_intersect($editable, ['phone','profile_picture'])) === 0)
                <p class="text-muted">No editable fields are enabled by the administrator.</p>
            @else
                <form method="post" action="{{ route('employee.profile.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    @if(in_array('phone', $editable, true))
                        <div class="form-group" style="margin-bottom:1rem;">
                            <label class="form-label" for="phone">Contact number</label>
                            <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                            @error('phone')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                    @endif
                    @if(in_array('profile_picture', $editable, true))
                        <div class="form-group" style="margin-bottom:1rem;">
                            <label class="form-label" for="profile_picture">Profile picture</label>
                            <input type="file" name="profile_picture" id="profile_picture" class="form-control" accept="image/*">
                            @error('profile_picture')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                    @endif
                    <button type="submit" class="btn btn--primary">Save changes</button>
                </form>
            @endif
            <p class="text-muted" style="margin-top:1rem;font-size:.85rem;">Employee ID, name, department, position, and employment status can only be changed by an administrator.</p>
        </div>
    </div>
</div>
@endsection
