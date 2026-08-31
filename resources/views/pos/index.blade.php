@extends('layouts.app')

@section('title', 'Sales History')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/inventory.css') }}">
<link rel="stylesheet" href="{{ asset('css/pos.css') }}">
@endpush

@section('content')
<div class="pos-module inv-module">
    <header class="inv-page-header">
        <div class="inv-page-header__left">
            <span class="inv-page-header__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            </span>
            <div>
                <h1 class="inv-page-header__title">Sales History</h1>
                <p class="inv-page-header__desc">View completed and voided POS transactions.</p>
            </div>
        </div>
        <div class="inv-page-header__actions">
            <a href="{{ route('pos.terminal') }}" class="btn btn--primary">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Open POS
            </a>
        </div>
    </header>

    {{-- Filters --}}
    <div class="card inv-filters">
        <div class="card__body">
            <form id="pos-filters" method="get" action="{{ route('pos.index') }}">
                <div class="inv-filters__top">
                    <div class="inv-search">
                        <svg class="inv-search__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="search" name="search" id="pos-history-search" class="inv-search__input" value="{{ request('search') }}" placeholder="Search sale number or customer…" aria-label="Search sales">
                    </div>
                    <div class="inv-filters__actions">
                        <button type="button" class="btn btn--secondary inv-filters__toggle" id="pos-filters-toggle" aria-expanded="false" aria-controls="pos-filters-advanced pos-filters-mobile">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                            Filters
                        </button>
                        <button type="submit" class="btn btn--primary">Apply Filters</button>
                        <a href="{{ route('pos.index') }}" class="btn btn--ghost">Clear Filters</a>
                    </div>
                </div>

                <div class="inv-filters__advanced inv-filters__advanced-desktop" id="pos-filters-advanced">
                    <div class="inv-filters__advanced-inner">
                        <div class="inv-filters__grid">
                            <div class="form-group">
                                <label class="form-label" for="pos-status">Status</label>
                                <select name="status" id="pos-status" class="form-select">
                                    <option value="">All</option>
                                    @foreach(['completed','voided'] as $st)
                                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="pos-payment">Payment</label>
                                <select name="payment_method" id="pos-payment" class="form-select">
                                    <option value="">All</option>
                                    @foreach($paymentMethods as $method)
                                        <option value="{{ $method }}" @selected(request('payment_method') === $method)>{{ \App\Models\Sale::paymentLabel($method) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="pos-date-from">From</label>
                                <input type="date" name="date_from" id="pos-date-from" class="form-control" value="{{ request('date_from') }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="pos-date-to">To</label>
                                <input type="date" name="date_to" id="pos-date-to" class="form-control" value="{{ request('date_to') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="inv-active-filters" id="pos-active-filters" aria-live="polite"></div>
            </form>
        </div>
    </div>

    {{-- Mobile filter drawer --}}
    <div class="inv-filters__drawer-backdrop" id="pos-filters-backdrop" aria-hidden="true"></div>
    <div class="inv-filters__advanced-mobile" id="pos-filters-mobile" role="dialog" aria-label="Filter sales" aria-modal="true">
        <h2 class="card__title" style="margin-bottom:1rem;">Filters</h2>
        <div class="inv-filters__grid">
            <div class="form-group">
                <label class="form-label" for="pos-status-mobile">Status</label>
                <select id="pos-status-mobile" class="form-select" data-pos-sync="status">
                    <option value="">All</option>
                    @foreach(['completed','voided'] as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="pos-payment-mobile">Payment</label>
                <select id="pos-payment-mobile" class="form-select" data-pos-sync="payment_method">
                    <option value="">All</option>
                    @foreach($paymentMethods as $method)
                        <option value="{{ $method }}" @selected(request('payment_method') === $method)>{{ \App\Models\Sale::paymentLabel($method) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="pos-date-from-mobile">From</label>
                <input type="date" id="pos-date-from-mobile" class="form-control" data-pos-sync="date_from" value="{{ request('date_from') }}">
            </div>
            <div class="form-group">
                <label class="form-label" for="pos-date-to-mobile">To</label>
                <input type="date" id="pos-date-to-mobile" class="form-control" data-pos-sync="date_to" value="{{ request('date_to') }}">
            </div>
        </div>
        <div class="btn-group mt-2">
            <button type="button" class="btn btn--primary btn--block" id="pos-filters-mobile-apply">Apply Filters</button>
        </div>
    </div>

    {{-- Sales table --}}
    <div class="card">
        <div class="card__body">
            @if($sales->isEmpty())
                <div class="inv-state">
                    <svg class="inv-state__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <h2 class="inv-state__title">No sales found</h2>
                    <p class="inv-state__text">
                        @if(request()->hasAny(['search', 'status', 'payment_method', 'date_from', 'date_to']))
                            Try adjusting your filters.
                        @else
                            Complete a sale from the POS terminal to see it here.
                        @endif
                    </p>
                    <a href="{{ route('pos.terminal') }}" class="btn btn--primary">Open POS</a>
                </div>
            @else
                <div class="table-wrap pos-table-desktop">
                    <table class="pos-data-table" aria-label="Sales history">
                        <thead>
                            <tr>
                                <th scope="col">Sale</th>
                                <th scope="col">Date</th>
                                <th scope="col">Customer</th>
                                <th scope="col">Payment</th>
                                <th scope="col">Total</th>
                                <th scope="col">Cashier</th>
                                <th scope="col">Status</th>
                                <th scope="col"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($sales as $row)
                            <tr>
                                <td>
                                    <a href="{{ route('pos.show', $row) }}" class="pos-sale-link">
                                        <span class="pos-sale-link__num">{{ $row->sale_number }}</span>
                                    </a>
                                </td>
                                <td>
                                    <div>{{ $row->sale_date?->format('M d, Y') ?? '—' }}</div>
                                    <div class="text-muted" style="font-size:0.78rem;">{{ $row->created_at ? ph_datetime($row->created_at) : '' }}</div>
                                </td>
                                <td>{{ $row->customer_name ?: 'Walk-in' }}</td>
                                <td>{{ \App\Models\Sale::paymentLabel($row->payment_method) }}</td>
                                <td><span class="pos-amount">{{ money($row->total_amount) }}</span></td>
                                <td>{{ $row->cashier?->displayName() ?? '—' }}</td>
                                <td>@include('partials.sale-status-badge', ['status' => $row->status])</td>
                                <td><a href="{{ route('pos.show', $row) }}" class="btn btn--ghost btn--sm">View</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="pos-cards" aria-label="Sales history">
                    @foreach($sales as $row)
                        <article class="pos-card">
                            <div class="pos-card__head">
                                <a href="{{ route('pos.show', $row) }}" class="pos-sale-link">
                                    <span class="pos-sale-link__num">{{ $row->sale_number }}</span>
                                </a>
                                @include('partials.sale-status-badge', ['status' => $row->status])
                            </div>
                            <p class="text-muted" style="margin:0 0 0.75rem;font-size:0.85rem;">{{ $row->customer_name ?: 'Walk-in' }}</p>
                            <dl class="pos-card__meta">
                                <div>
                                    <dt>Date</dt>
                                    <dd>{{ $row->sale_date?->format('M d, Y') ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt>Payment</dt>
                                    <dd>{{ \App\Models\Sale::paymentLabel($row->payment_method) }}</dd>
                                </div>
                                <div>
                                    <dt>Total</dt>
                                    <dd class="pos-amount">{{ money($row->total_amount) }}</dd>
                                </div>
                                <div>
                                    <dt>Cashier</dt>
                                    <dd>{{ $row->cashier?->displayName() ?? '—' }}</dd>
                                </div>
                            </dl>
                            <a href="{{ route('pos.show', $row) }}" class="btn btn--secondary btn--sm btn--block">View Sale</a>
                        </article>
                    @endforeach
                </div>

                <div class="inv-pagination">
                    @include('partials.pagination', ['paginator' => $sales->withQueryString()])
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('pos-filters');
    const toggle = document.getElementById('pos-filters-toggle');
    const advanced = document.getElementById('pos-filters-advanced');
    const drawer = document.getElementById('pos-filters-mobile');
    const backdrop = document.getElementById('pos-filters-backdrop');
    const mobileApply = document.getElementById('pos-filters-mobile-apply');
    const activeEl = document.getElementById('pos-active-filters');
    const MOBILE = window.matchMedia('(max-width: 768px)');

    function renderChips() {
        if (!activeEl || !form) return;
        const chips = [];
        const fd = new FormData(form);
        const labels = { status: 'Status', payment_method: 'Payment', date_from: 'From', date_to: 'To' };
        fd.forEach((v, k) => {
            if (!v || k === 'search') return;
            chips.push('<span class="inv-filter-chip">' + (labels[k] || k) + ': ' + v + '</span>');
        });
        activeEl.innerHTML = chips.join('');
    }

    toggle?.addEventListener('click', () => {
        if (MOBILE.matches) {
            document.querySelectorAll('[data-pos-sync]').forEach((el) => {
                const source = form.querySelector('[name="' + el.dataset.posSync + '"]');
                if (source) el.value = source.value;
            });
            drawer?.classList.add('is-open');
            backdrop?.classList.add('is-visible');
            document.body.style.overflow = 'hidden';
        } else {
            advanced?.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', advanced?.classList.contains('is-open') ? 'true' : 'false');
        }
    });

    backdrop?.addEventListener('click', () => {
        drawer?.classList.remove('is-open');
        backdrop?.classList.remove('is-visible');
        document.body.style.overflow = '';
    });

    mobileApply?.addEventListener('click', () => {
        document.querySelectorAll('[data-pos-sync]').forEach((el) => {
            const target = form.querySelector('[name="' + el.dataset.posSync + '"]');
            if (target) target.value = el.value;
        });
        form.submit();
    });

    if (form?.querySelector('[name="status"]')?.value || form?.querySelector('[name="payment_method"]')?.value) {
        advanced?.classList.add('is-open');
        toggle?.setAttribute('aria-expanded', 'true');
        toggle?.classList.add('is-active');
    }

    renderChips();
})();
</script>
@endpush
