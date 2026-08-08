@extends('layouts.app')
@section('title', 'New location')
@section('content')
<div class="page-header"><div><h1>New location</h1></div><a href="{{ route('locations.index') }}" class="btn btn--secondary">Back</a></div>
<div class="card"><div class="card__body"><form method="post" action="{{ route('locations.store') }}">@csrf
<div class="form-grid">
<div class="form-group"><label class="form-label" for="name">Name <span class="req">*</span></label><input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required></div>
<div class="form-group"><label class="form-label" for="code">Code</label><input type="text" name="code" id="code" class="form-control" value="{{ old('code') }}"></div>
<div class="form-group"><label class="form-label" for="building">Building</label><input type="text" name="building" id="building" class="form-control" value="{{ old('building') }}"></div>
<div class="form-group"><label class="form-label" for="office">Office</label><input type="text" name="office" id="office" class="form-control" value="{{ old('office') }}"></div>
<div class="form-group"><label class="form-label" for="floor">Floor</label><input type="text" name="floor" id="floor" class="form-control" value="{{ old('floor') }}"></div>
<div class="form-group form-group--full"><label class="form-label" for="description">Description</label><textarea name="description" id="description" class="form-textarea">{{ old('description') }}</textarea></div>
<div class="form-check form-group--full"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> Active</div>
</div>
<button type="submit" class="btn btn--primary mt-2">Save</button>
</form></div></div>
@endsection
