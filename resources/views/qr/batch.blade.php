@extends('layouts.app')

@section('title', 'Batch QR labels')

@section('content')
<div class="page-header">
    <div>
        <h1>Batch QR labels</h1>
        <p class="page-header__meta">Select items and choose a print layout</p>
    </div>
    <a href="{{ route('inventory.index') }}" class="btn btn--secondary">Back</a>
</div>

<div class="card">
    <div class="card__body">
        <form method="post" action="{{ route('qr.batch.generate') }}">
            @csrf
            <div class="form-group mb-2">
                <label class="form-label">Layout</label>
                <div class="btn-group">
                    @foreach(['1' => '1 per page', '2' => '2 per row', '4' => '4 grid', '8' => '8 grid', 'label' => 'Label sheet'] as $val => $label)
                        <label class="form-check">
                            <input type="radio" name="layout" value="{{ $val }}" @checked(old('layout', '4') === $val) required>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Items</label>
                <div class="table-wrap" style="max-height:400px;overflow:auto;border:1px solid var(--border);border-radius:8px;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width:40px;"><input type="checkbox" id="select-all-items"></th>
                                <th>Code</th>
                                <th>Name</th>
                                <th>QR</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($items as $row)
                            <tr>
                                <td><input type="checkbox" name="item_ids[]" value="{{ $row->id }}" class="item-check"></td>
                                <td>{{ $row->item_code }}</td>
                                <td>{{ $row->name }}</td>
                                <td class="text-muted">{{ Str::limit($row->qr_code, 24) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><div class="empty-state">No active items.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <button type="submit" class="btn btn--primary mt-2">Generate printable sheet</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('select-all-items')?.addEventListener('change', function () {
    document.querySelectorAll('.item-check').forEach(cb => { cb.checked = this.checked; });
});
</script>
@endpush
