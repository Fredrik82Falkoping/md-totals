@extends('layouts.app')

@section('content')

    <h1>MD Totals — {{ $tenant_name }}</h1>

    <div class="summary">
        <div>
            <strong>{{ $summary['total_count'] }}</strong>
            Totalt antal nedsatta priser
        </div>
        <div>
            <strong>{{ number_format($summary['total_discount'], 2) }} kr</strong>
            Total nedsatt summa
        </div>
        <div>
            <strong>{{ number_format($summary['total_margin'], 2) }} kr</strong>
            Total marginal
        </div>
        <div>
            <strong>{{ number_format($summary['average_discount_percent'], 1) }}%</strong>
            Genomsnittlig rabatt
        </div>
    </div>

    <form method="GET" action="{{ route('statistics.index') }}" id="filterForm" class="filters">
        
        <div class="filter-group">
            <label for="category">Kategori</label>
            <select name="category[]" id="category" multiple>
                <option value="">Alla kategorier</option>
                @foreach ($categories as $category)
                    <option value="{{ $category }}" @selected(in_array($category, request()->input('category', [])))>
                        {{ $category }}
                    </option>
                @endforeach
            </select>
        </div>
    
        <div class="filter-group">
            <label for="week">Veckor</label>
            <select name="week[]" id="week" multiple>
                <option value="">Alla veckor</option>
                @foreach ($weeks as $week)
                    <option value="{{ $week }}" @selected(in_array($week, request()->input('week', [])))>
                        {{ $week }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label for="month">Månader</label>
            <select name="month[]" id="month" multiple>
                <option value="">Alla månader</option>
                @foreach ($months as $month)
                    <option value="{{ $month }}" @selected(in_array($month, request()->input('month', [])))>
                        {{ $month }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label for="year">År</label>
            <select name="year[]" id="year" multiple>
                <option value="">Alla år</option>
                @foreach ($years as $year)
                    <option value="{{ $year }}"  @selected(in_array($year, request()->input('year', [])))>
                        {{ $year }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label for="discount_percent">Rabatt %</label>
            <select name="discount_percent[]" id="discount_percent" multiple>
                @foreach ($discountPercents as $percent)
                    <option value="{{ $percent }}" @selected(in_array($percent, $currentDiscountPercents))>
                        {{ number_format($percent, 0) }}%
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="filter-button">Filtrera</button>

        <input type="hidden" name="sort" value="{{ $currentSort }}">
        <input type="hidden" name="direction" value="{{ $currentDirection }}">
    </form>

    <div id="loadingOverlay" class="loading-overlay">
        <div class="spinner"></div>
    </div>

    <h2>Nedsatta priser</h2>
    <table>
        <thead>
            <tr>
                @php
                    // Dina nya önskade kolumner
                    $columns = [
                        'product_name' => 'Produktnamn',
                        'quantity' => 'Antal',
                        'purchase_price' => 'Total kronor inköp',
                        'reduced_price' => 'Total kronor nedsatt',
                        'margin_amount' => 'Total förtjänst',
                        'discount_percent' => 'Nedsatt i %',
                        'margin_percent' => 'Medelmarginal i %',
                    ];
                @endphp
                @foreach ($columns as $key => $label)
                    @php
                        $nextDirection = ($currentSort === $key && $currentDirection === 'asc') ? 'desc' : 'asc';
                    @endphp
                    <th>
                        <a href="{{ route('statistics.index', array_merge(request()->query(), ['sort' => $key, 'direction' => $nextDirection])) }}"
                        class="sort-link {{ $currentSort === $key ? 'active-' . $currentDirection : '' }}">
                            {{ $label }}
                            @if ($currentSort === $key)
                                {!! $currentDirection === 'asc' ? '&#8593;' : '&#8595;' !!}
                            @endif
                        </a>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($markdowns as $markdown)
                <tr class="product-row" data-product-id="{{ $markdown->product_id }}">
                    <!-- Produktnamn (eller ID om namn saknas) -->
                    <td>{{ $markdown->product_name ?? $markdown->product_id }}</td>
                    
                    <!-- Antal (Visar stycken. Om din app använder vikt, kan du lägga till logik för weight_kg här) -->
                    <td>{{ number_format($markdown->total_scans, 0, ',', ' ') }} st</td>
                    
                    <!-- Total kronor inköp -->
                    <td>{{ number_format($markdown->total_purchase_price, 2, ',', ' ') }} kr</td>
                    
                    <!-- Total kronor nedsatt -->
                    <td>{{ number_format($markdown->total_reduced_price, 2, ',', ' ') }} kr</td>
                    
                    <!-- Total förtjänst (marginal i kronor) -->
                    <td>{{ number_format($markdown->total_margin_amount, 2, ',', ' ') }} kr</td>
                    
                    <!-- Nedsatt i % (Genomsnittlig rabatt) -->
                    <td>{{ number_format($markdown->avg_discount_percent, 1, ',', ' ') }}%</td>
                    
                    <!-- Medelmarginal i % -->
                    <td>{{ number_format($markdown->avg_margin_percent, 1, ',', ' ') }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center;">Inga produkter matchade sökningen.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div id="productModal" class="modal-overlay">
        <div class="modal-box">
            <button id="closeModal" class="modal-close">&times;</button>
            <h2 id="modalTitle"></h2>
            <table class="modal-table">
                <thead>
                    <tr>
                        <th>Scanned at</th>
                        <th>Regular price</th>
                        <th>Reduced price</th>
                        <th>Discount</th>
                        <th>Discount %</th>
                    </tr>
                </thead>
                <tbody id="modalTableBody"></tbody>
            </table>
        </div>
    </div>

@endsection