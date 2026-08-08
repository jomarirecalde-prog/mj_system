@extends('layouts.app')
@section('title', 'New supplier')
@section('content')
<div class="page-header"><div><h1>New supplier</h1></div><a href="{{ route('suppliers.index') }}" class="btn btn--secondary">Back</a></div>
<div class="card"><div class="card__body"><form method="post" action="{{ route('suppliers.store') }}">@csrf
<div class="form-grid">
<div class="form-group"><label class="form-label" for="name">Name <span class="req">*</span></label><input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required></div>
<div class="form-group"><label class="form-label" for="contact_person">Contact person</label><input type="text" name="contact_person" id="contact_person" class="form-control" value="{{ old('contact_person') }}"></div>
<div class="form-group"><label class="form-label" for="email">Email</label><input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}"></div>
<div class="form-group"><label class="form-label" for="phone">Phone</label><input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone') }}"></div>
<div class="form-group form-group--full"><label class="form-label" for="address">Address</label><textarea name="address" id="address" class="form-textarea">{{ old('address') }}</textarea></div>
<div class="form-check form-group--full"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> Active</div>
</div><button type="submit" class="btn btn--primary mt-2">Save</button></form></div></div>
@endsection
