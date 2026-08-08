@extends('layouts.app')

@section('title', 'Inventory')

@section('content')
<div class="page-header">
    <div>
        <h1>Inventory</h1>
        <p class="page-header__meta">Search, filter, and manage all items</p>
    </div>
    <div class="btn-group">
        @if(auth()->user()->canModifyInventory())
            <a href="{{ route('inventory.create') }}" class="btn btn--primary">New item</a>
        @endif
        <a href="{{ route('inventory.export', request()->query()) }}" class="btn btn--secondary">Export Excel</a>
    </div>
</div>

<div class="card mb-2">
    <div class="card__body">
        <form id="inventory-filters" class="filters" method="get" action="{{ route('inventory.index') }}">
            <div class="form-group">
                <label class="form-label" for="search">Search</label>
                <input type="search" name="search" id="search" class="form-control" value="{{ request('search') }}" placeholder="Code, name, serial…">
            </div>
            <div class="form-group">
                <label class="form-label" for="category_id">Category</label>
                <select name="category_id" id="category_id" class="form-select">
                    <option value="">All</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="location_id">Location</label>
                <select name="location_id" id="location_id" class="form-select">
                    <option value="">All</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" @selected(request('location_id') == $loc->id)>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="inventory_type">Type</label>
                <select name="inventory_type" id="inventory_type" class="form-select">
                    <option value="">All</option>
                    <option value="consumable" @selected(request('inventory_type') === 'consumable')>Consumable</option>
                    <option value="asset" @selected(request('inventory_type') === 'asset')>Asset</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="status">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">All</option>
                    @foreach(['Available', 'Borrowed', 'Under Maintenance', 'Out of Stock'] as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ $st }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="condition">Condition</label>
                <select name="condition" id="condition" class="form-select">
                    <option value="">All</option>
                    @foreach(['New', 'Good', 'Fair', 'Damaged', 'For Maintenance', 'Lost', 'Disposed'] as $cond)
                        <option value="{{ $cond }}" @selected(request('condition') === $cond)>{{ $cond }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-check" style="margin-top:1.5rem;">
                    <input type="checkbox" name="low_stock" value="1" @checked(request()->boolean('low_stock'))> Low stock only
                </label>
            </div>
            <button type="submit" class="btn btn--primary">Apply</button>
            <a href="{{ route('inventory.index') }}" class="btn btn--secondary">Reset</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card__body table-wrap">
        <div id="inventory-loading" class="loading-overlay">
            <span class="spinner"></span> Loading inventory…
        </div>
        <table class="data-table" id="inventory-table" style="display:none;">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Location</th>
                    <th>Qty</th>
                    <th>Status</th>
                    <th>Condition</th>
                    <th>Value</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="inventory-tbody"></tbody>
        </table>
        <div id="inventory-empty" class="empty-state" style="display:none;">
            <p class="empty-state__title">No items found</p>
            <p class="text-muted">Try adjusting your filters or add a new item.</p>
        </div>
    </div>
    <div class="card__body" style="padding-top:0;">
        <div class="pagination" id="inventory-pagination"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('inventory-filters');
    const tbody = document.getElementById('inventory-tbody');
    const table = document.getElementById('inventory-table');
    const loading = document.getElementById('inventory-loading');
    const empty = document.getElementById('inventory-empty');
    const pagination = document.getElementById('inventory-pagination');
    const baseUrl = @json(route('inventory.index'));
    const inventoryBase = @json(url('inventory'));
    let currentPage = 1;

    function statusBadge(status) {
        const map = {
            'Available': 'badge--available',
            'Borrowed': 'badge--borrowed',
            'Under Maintenance': 'badge--maintenance',
            'Archived': 'badge--archived',
            'Out of Stock': 'badge--out'
        };
        const cls = map[status] || 'badge--default';
        return '<span class="badge ' + cls + '">' + (status || '—') + '</span>';
    }

    function buildQuery(page) {
        const fd = new FormData(form);
        const params = new URLSearchParams();
        fd.forEach((v, k) => { if (v) params.set(k, v); });
        params.set('page', String(page || 1));
        return params.toString();
    }

    function renderRows(items) {
        const canModify = @json(auth()->user()->canModifyInventory());
        return items.map(row => {
            const showUrl = inventoryBase + '/' + row.id;
            let actions = '<a href="' + showUrl + '" class="btn btn--ghost btn--sm">View</a>';
            if (canModify) {
                actions += ' <a href="' + showUrl + '/edit" class="btn btn--secondary btn--sm">Edit</a>';
            }
            return '<tr>' +
                '<td><a href="' + showUrl + '">' + row.item_code + '</a></td>' +
                '<td>' + row.name + '</td>' +
                '<td>' + (row.category ? row.category.name : '—') + '</td>' +
                '<td>' + (row.location ? row.location.name : '—') + '</td>' +
                '<td>' + row.quantity + '</td>' +
                '<td>' + statusBadge(row.status) + '</td>' +
                '<td>' + (row.condition || '—') + '</td>' +
                '<td>' + App.formatMoney(row.total_value) + '</td>' +
                '<td><div class="actions">' + actions + '</div></td>' +
                '</tr>';
        }).join('');
    }

    function renderPagination(meta) {
        if (!meta || meta.last_page <= 1) {
            pagination.innerHTML = '';
            return;
        }
        let html = '';
        const mk = (p, label, active, disabled) =>
            '<button type="button" class="pagination__btn' + (active ? ' is-active' : '') + '" data-page="' + p + '" ' + (disabled ? 'disabled' : '') + '>' + label + '</button>';
        html += mk(meta.current_page - 1, 'Prev', false, meta.current_page <= 1);
        html += '<span class="text-muted" style="padding:0 0.5rem;">Page ' + meta.current_page + ' of ' + meta.last_page + '</span>';
        html += mk(meta.current_page + 1, 'Next', false, meta.current_page >= meta.last_page);
        pagination.innerHTML = html;
        pagination.querySelectorAll('[data-page]').forEach(btn => {
            btn.addEventListener('click', () => load(parseInt(btn.dataset.page, 10)));
        });
    }

    async function load(page) {
        currentPage = page || 1;
        loading.style.display = 'flex';
        table.style.display = 'none';
        empty.style.display = 'none';
        try {
            const url = baseUrl + '?' + buildQuery(currentPage);
            const data = await App.fetchJson(url, { headers: { Accept: 'application/json' } });
            const items = data.data || [];
            loading.style.display = 'none';
            if (!items.length) {
                empty.style.display = 'block';
                tbody.innerHTML = '';
                renderPagination(data);
                return;
            }
            table.style.display = 'table';
            tbody.innerHTML = renderRows(items);
            renderPagination(data);
        } catch (e) {
            loading.style.display = 'none';
            empty.style.display = 'block';
            empty.querySelector('.empty-state__title').textContent = 'Could not load inventory';
            empty.querySelector('.text-muted').textContent = e.message;
            App.toast(e.message, 'error');
        }
    }

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        load(1);
    });

    ['search', 'category_id', 'location_id', 'status', 'condition'].forEach(id => {
        const el = document.getElementById(id);
        el?.addEventListener('change', () => load(1));
    });
    document.getElementById('search')?.addEventListener('input', App.debounce(() => load(1), 400));

    load(1);
})();
</script>
@endpush
