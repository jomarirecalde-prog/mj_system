@extends('layouts.app')

@section('title', 'New item')

@section('content')
<div class="page-header">
    <div>
        <h1>Add inventory item</h1>
        <p class="page-header__meta">Register a new asset with QR tracking</p>
    </div>
    <a href="{{ route('inventory.index') }}" class="btn btn--secondary">Back to list</a>
</div>

<div class="card">
    <div class="card__body">
        <form method="post" action="{{ route('inventory.store') }}">
            @csrf
            @include('inventory._form')
            <div class="btn-group mt-2">
                <button type="submit" class="btn btn--primary">Create item</button>
                <a href="{{ route('inventory.index') }}" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
