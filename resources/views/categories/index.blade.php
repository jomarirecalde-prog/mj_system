@extends('layouts.app')

@section('title', 'Categories')

@section('content')
<div class="page-header">
    <div><h1>Categories</h1><p class="page-header__meta">Organize inventory by category</p></div>
    @if(auth()->user()->isAdmin())
        <a href="{{ route('categories.create') }}" class="btn btn--primary">Add category</a>
    @endif
</div>

<div class="card mb-2">
    <div class="card__body">
        <form method="get" class="filters">
            <div class="form-group"><label class="form-label" for="search">Search</label>
                <input type="search" name="search" id="search" class="form-control" value="{{ request('search') }}"></div>
            <button type="submit" class="btn btn--secondary">Search</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card__body table-wrap">
        @if($categories->isEmpty())
            <div class="empty-state"><p class="empty-state__title">No categories</p></div>
        @else
            <table class="data-table">
                <thead><tr><th>Name</th><th>Code</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @foreach($categories as $row)
                    <tr>
                        <td><a href="{{ route('categories.show', $row) }}">{{ $row->name }}</a></td>
                        <td>{{ $row->code ?? '—' }}</td>
                        <td><span class="badge {{ $row->is_active ? 'badge--available' : 'badge--archived' }}">{{ $row->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="actions">
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('categories.edit', $row) }}" class="btn btn--ghost btn--sm">Edit</a>
                                <form action="{{ route('categories.destroy', $row) }}" method="post" data-confirm="Delete this category?" style="display:inline;">@csrf @method('DELETE')<button type="submit" class="btn btn--danger btn--sm">Delete</button></form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @include('partials.pagination', ['paginator' => $categories->withQueryString()])
        @endif
    </div>
</div>
@endsection
