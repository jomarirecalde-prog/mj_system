@extends('layouts.app')
@section('title', 'New purchase')
@section('content')
<div class="page-header">
    <div>
        <h1>New purchase</h1>
        <p class="page-header__meta">Quantities are added to inventory only after receiving</p>
    </div>
    <a href="{{ route('purchases.index') }}" class="btn btn--secondary">Cancel</a>
</div>

<div class="card">
    <div class="card__body">
        <form method="post" action="{{ route('purchases.store') }}" id="purchase-form">
            @csrf
            @include('purchases._form')
            <button type="submit" class="btn btn--primary mt-2">Create purchase</button>
        </form>
    </div>
</div>
@endsection
