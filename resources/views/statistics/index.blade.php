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
            <strong>{{ number_format($summary['average_discount_percent'], 1) }}%</strong>
            Genomsnittlig rabatt
        </div>
    </div>

    <form method="GET" action="{{ route('statistics.index') }}" id="filterForm" class="filters">
        
        <div class="filter-group">
            <label for="category">Kategori</label>
            <select name="category" id="category">
                <option value="">Alla kategorier</option>
                @foreach ($categories as $category)
                    <option value="{{ $category }}" @selected($currentCategory === $category)>
                        {{ $category }}
                    </option>
                @endforeach
            </select>
        </div>
    
        <div class="filter-group">
            <label for="week">Veckor</label>
            <select name="week" id="week">
                <option value="">Alla veckor</option>
                @foreach ($weeks as $week)
                    <option value="{{ $week }}" @selected($currentWeek === $week)>
                        {{ $week }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label for="month">Månader</label>
            <select name="month" id="month">
                <option value="">Alla månader</option>
                @foreach ($months as $month)
                    <option value="{{ $month }}" @selected($currentWeek === $month)>
                        {{ $month }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label for="year">År</label>
            <select name="year" id="year">
                <option value="">Alla år</option>
                @foreach ($years as $year)
                    <option value="{{ $year }}" @selected($currentWeek === $year)>
                        {{ $year }}
                    </option>
                @endforeach
            </select>
        </div>

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
                    $columns = [
                        'product_name' => 'Produktnamn',
                        'category' => 'Kategori',
                        'scanned_at' => 'Scannad',
                        'regular_price' => 'Ord. pris',
                        'reduced_price' => 'Nedsatt pris',
                        'discount_amount' => 'Rabatt',
                        'discount_percent' => 'Rabatt %',
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
                        </a>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($markdowns as $markdown)
                <tr>
                    <td>{{ $markdown->name ?? $markdown->product_id }}</td>
                    <td>{{ $markdown->category ?? '—' }}</td>
                    <td>{{ $markdown->scanned_at }}</td>
                    <td>{{ number_format($markdown->regular_price, 2) }} kr</td>
                    <td>{{ number_format($markdown->reduced_price, 2) }} kr</td>
                    <td>{{ number_format($markdown->discount_amount, 2) }} kr</td>
                    <td>{{ number_format($markdown->discount_percent, 1) }}%</td>
                </tr>
            @empty
                <tr><td colspan="7">Ingen data hittades.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection