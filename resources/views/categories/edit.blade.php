@extends('layouts.app')
@section('title', 'Edit category')
@section('content')
<div class="page-header"><div><h1>Edit category</h1></div><a href="{{ route('categories.index') }}" class="btn btn--secondary">Back</a></div>
<div class="card"><div class="card__body">
<form method="post" action="{{ route('categories.update', $category) }}">@csrf @method('PUT')
<div class="form-grid form-grid--1">
<div class="form-group"><label class="form-label" for="name">Name <span class="req">*</span></label><input type="text" name="name" id="name" class="form-control" value="{{ old('name', $category->name) }}" required></div>
<div class="form-group"><label class="form-label" for="code">Code</label><input type="text" name="code" id="code" class="form-control" value="{{ old('code', $category->code) }}"></div>
<div class="form-group"><label class="form-label" for="description">Description</label><textarea name="description" id="description" class="form-textarea">{{ old('description', $category->description) }}</textarea></div>
<div class="form-check"><input type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', $category->is_active))><label for="is_active">Active</label></div>
</div>
<button type="submit" class="btn btn--primary mt-2">Update</button>
</form></div></div>
@endsection
