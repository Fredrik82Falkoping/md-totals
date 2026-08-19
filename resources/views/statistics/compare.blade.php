@extends('layouts.app')

@section('content')

<h1>MD Totals — Jämför perioder — {{ $tenant_name }}</h1>

<form method="GET" action="{{ route('statistics.compare') }}" id="compareForm" class="filters">

    <div class="filter-group">
        <label for="category">Kategori</label>
        <select name="category[]" id="category" multiple>
            @foreach ($allCategories as $cat)
                <option value="{{ $cat }}" @selected(in_array($cat, $currentCategories))>
                    {{ $cat }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="filter-group">
        <label>Periodtyp</label>
        <div class="period-type-toggle">
            <label>
                <input type="radio" name="period_type" value="week" @checked($periodType === 'week')>
                Vecka
            </label>
            <label>
                <input type="radio" name="period_type" value="month" @checked($periodType === 'month')>
                Månad
            </label>
            <label>
                <input type="radio" name="period_type" value="year" @checked($periodType === 'year')>
                År
            </label>
        </div>
    </div>

    <div class="filter-group period-select" data-type="week">
        <label for="period_a_week">Period A (vecka)</label>
        <select id="period_a_week" class="period-a-input" data-type="week">
            <option value="">-- Välj vecka --</option>
            @foreach ($weeks as $week)
                <option value="{{ $week }}" @selected($periodType === 'week' && $periodAValue === $week)>{{ $week }}</option>
            @endforeach
        </select>
    </div>

    <div class="filter-group period-select" data-type="month">
        <label for="period_a_month">Period A (månad)</label>
        <select id="period_a_month" class="period-a-input" data-type="month">
            <option value="">-- Välj månad --</option>
            @foreach ($months as $month)
                <option value="{{ $month }}" @selected($periodType === 'month' && $periodAValue === $month)>{{ $month }}</option>
            @endforeach
        </select>
    </div>

    <div class="filter-group period-select" data-type="year">
        <label for="period_a_year">Period A (år)</label>
        <select id="period_a_year" class="period-a-input" data-type="year">
            <option value="">-- Välj år --</option>
            @foreach ($years as $year)
                <option value="{{ $year }}" @selected($periodType === 'year' && $periodAValue === $year)>{{ $year }}</option>
            @endforeach
        </select>
    </div>

    <div class="filter-group period-select" data-type="week">
        <label for="period_b_week">Period B (vecka)</label>
        <select id="period_b_week" class="period-b-input" data-type="week">
            <option value="">-- Välj vecka --</option>
            @foreach ($weeks as $week)
                <option value="{{ $week }}" @selected($periodType === 'week' && $periodBValue === $week)>{{ $week }}</option>
            @endforeach
        </select>
    </div>

    <div class="filter-group period-select" data-type="month">
        <label for="period_b_month">Period B (månad)</label>
        <select id="period_b_month" class="period-b-input" data-type="month">
            <option value="">-- Välj månad --</option>
            @foreach ($months as $month)
                <option value="{{ $month }}" @selected($periodType === 'month' && $periodBValue === $month)>{{ $month }}</option>
            @endforeach
        </select>
    </div>

    <div class="filter-group period-select" data-type="year">
        <label for="period_b_year">Period B (år)</label>
        <select id="period_b_year" class="period-b-input" data-type="year">
            <option value="">-- Välj år --</option>
            @foreach ($years as $year)
                <option value="{{ $year }}" @selected($periodType === 'year' && $periodBValue === $year)>{{ $year }}</option>
            @endforeach
        </select>
    </div>

    <input type="hidden" name="period_a" id="period_a_hidden" value="{{ $periodAValue }}">
    <input type="hidden" name="period_b" id="period_b_hidden" value="{{ $periodBValue }}">

    <button type="submit" class="filter-button">Jämför</button>
</form>

<div class="compare-columns">

    <div class="compare-column">
        <h2>Period A</h2>
        @if ($summaryA)
            <table class="summary-table">
                <tr><th>Antal nedsättningar</th><td>{{ $summaryA['total_count'] }}</td></tr>
                <tr><th>Total rabatt</th><td>{{ number_format($summaryA['total_discount'], 2) }} kr</td></tr>
                <tr><th>Snitt rabatt %</th><td>{{ number_format($summaryA['average_discount_percent'] ?? 0, 1) }}%</td></tr>
                <tr><th>Totalt ordinarie värde</th><td>{{ number_format($summaryA['total_regular_value'], 2) }} kr</td></tr>
                <tr><th>Totalt nedsatt värde</th><td>{{ number_format($summaryA['total_reduced_value'], 2) }} kr</td></tr>
            </table>

            <h3>Nedsättningar i perioden</h3>
            <table class="detail-table">
                <thead>
                    <tr>
                        <th>Produkt-ID</th>
                        <th>Kategori</th>
                        <th>Datum</th>
                        <th>Ord. pris</th>
                        <th>Nedsatt pris</th>
                        <th>Rabatt</th>
                        <th>Rabatt %</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($markdownsA as $m)
                        <tr>
                            <td>{{ $m->product_id }}</td>
                            <td>{{ $m->category ?? '—' }}</td>
                            <td>{{ $m->scanned_at }}</td>
                            <td>{{ number_format($m->regular_price, 2) }} kr</td>
                            <td>{{ number_format($m->reduced_price, 2) }} kr</td>
                            <td>{{ number_format($m->discount_amount, 2) }} kr</td>
                            <td>{{ number_format($m->discount_percent, 1) }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="7">Ingen data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @else
            <p>Välj period A.</p>
        @endif
    </div>

    <div class="compare-column">
        <h2>Period B</h2>
        @if ($summaryB)
            <table class="summary-table">
                <tr><th>Antal nedsättningar</th><td>{{ $summaryB['total_count'] }}</td></tr>
                <tr><th>Total rabatt</th><td>{{ number_format($summaryB['total_discount'], 2) }} kr</td></tr>
                <tr><th>Snitt rabatt %</th><td>{{ number_format($summaryB['average_discount_percent'] ?? 0, 1) }}%</td></tr>
                <tr><th>Totalt ordinarie värde</th><td>{{ number_format($summaryB['total_regular_value'], 2) }} kr</td></tr>
                <tr><th>Totalt nedsatt värde</th><td>{{ number_format($summaryB['total_reduced_value'], 2) }} kr</td></tr>
            </table>

            <h3>Nedsättningar i perioden</h3>
            <table class="detail-table">
                <thead>
                    <tr>
                        <th>Produkt-ID</th>
                        <th>Kategori</th>
                        <th>Datum</th>
                        <th>Ord. pris</th>
                        <th>Nedsatt pris</th>
                        <th>Rabatt</th>
                        <th>Rabatt %</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($markdownsA as $m)
                        <tr>
                            <td>{{ $m->product_id }}</td>
                            <td>{{ $m->category ?? '—' }}</td>
                            <td>{{ $m->scanned_at }}</td>
                            <td>{{ number_format($m->regular_price, 2) }} kr</td>
                            <td>{{ number_format($m->reduced_price, 2) }} kr</td>
                            <td>{{ number_format($m->discount_amount, 2) }} kr</td>
                            <td>{{ number_format($m->discount_percent, 1) }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="7">Ingen data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @else
            <p>Välj period B.</p>
        @endif
    </div>

</div>

@if ($summaryA && $summaryB)
    @php
        $diffCount = $summaryB['total_count'] - $summaryA['total_count'];
        $diffDiscount = $summaryB['total_discount'] - $summaryA['total_discount'];
    @endphp
    <div class="compare-diff">
        <h2>Skillnad (B − A)</h2>
        <p>Antal: {{ $diffCount >= 0 ? '+' : '' }}{{ $diffCount }}</p>
        <p>Total rabatt: {{ $diffDiscount >= 0 ? '+' : '' }}{{ number_format($diffDiscount, 2) }} kr</p>
    </div>
@endif

@endsection