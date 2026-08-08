@extends('layouts.app')
@section('title', 'New department')
@section('content')
<div class="page-header"><div><h1>New department</h1></div><a href="{{ route('departments.index') }}" class="btn btn--secondary">Back</a></div>
<div class="card"><div class="card__body"><form method="post" action="{{ route('departments.store') }}">@csrf
<div class="form-grid form-grid--1">
<div class="form-group"><label class="form-label" for="name">Name <span class="req">*</span></label><input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required></div>
<div class="form-group"><label class="form-label" for="code">Code</label><input type="text" name="code" id="code" class="form-control" value="{{ old('code') }}"></div>
<div class="form-group"><label class="form-label" for="description">Description</label><textarea name="description" id="description" class="form-textarea">{{ old('description') }}</textarea></div>
<div class="form-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> Active</div>
</div><button type="submit" class="btn btn--primary mt-2">Save</button></form></div></div>
@endsection
