@extends('layouts.app')

@section('title', 'Edit '.$item->item_code)

@section('content')
<div class="page-header">
    <div>
        <h1>Edit {{ $item->name }}</h1>
        <p class="page-header__meta">{{ $item->item_code }}</p>
    </div>
    <a href="{{ route('inventory.show', $item) }}" class="btn btn--secondary">View item</a>
</div>

<div class="card">
    <div class="card__body">
        <form method="post" action="{{ route('inventory.update', $item) }}">
            @csrf
            @method('PUT')
            @include('inventory._form', ['item' => $item])
            <div class="btn-group mt-2">
                <button type="submit" class="btn btn--primary">Save changes</button>
                <a href="{{ route('inventory.show', $item) }}" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
