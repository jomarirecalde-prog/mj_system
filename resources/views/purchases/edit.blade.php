@extends('layouts.app')
@section('title', 'Edit purchase')
@section('content')
<div class="page-header">
    <div>
        <h1>Edit purchase</h1>
        <p class="page-header__meta">{{ $purchase->purchase_number }}</p>
    </div>
    <a href="{{ route('purchases.show', $purchase) }}" class="btn btn--secondary">Cancel</a>
</div>

<div class="card">
    <div class="card__body">
        <form method="post" action="{{ route('purchases.update', $purchase) }}" id="purchase-form">
            @csrf
            @method('PUT')
            @include('purchases._form')
            <button type="submit" class="btn btn--primary mt-2">Save changes</button>
        </form>
    </div>
</div>
@endsection
