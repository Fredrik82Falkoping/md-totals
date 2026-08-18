@extends('layouts.app')

@section('content')

    <h1>MD Totals — {{ $tenant_name }}</h1>

    <div class="summary">
        <div>
            <strong>{{ $summary['total_count'] }}</strong>
            Total markdowns
        </div>
        <div>
            <strong>{{ number_format($summary['total_discount'], 2) }} kr</strong>
            Total discount amount
        </div>
        <div>
            <strong>{{ number_format($summary['average_discount_percent'], 1) }}%</strong>
            Average discount
        </div>
    </div>

    <form method="GET" action="{{ route('statistics.index') }}" id="filterForm" class="filters">
        
        <div class="filter-group">
            <label for="category">Category</label>
            <select name="category" id="category">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category }}" @selected($currentCategory === $category)>
                        {{ $category }}
                    </option>
                @endforeach
            </select>
        </div>
    
        <div class="filter-group">
            <label for="week">Week</label>
            <select name="week" id="week">
                <option value="">All weeks</option>
                @foreach ($weeks as $week)
                    <option value="{{ $week }}" @selected($currentWeek === $week)>
                        {{ $week }}
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

    <h2>Latest markdowns</h2>
    <table>
        <thead>
            <tr>
                @php
                    $columns = [
                        'product_id' => 'Product ID',
                        'category' => 'Category',
                        'scanned_at' => 'Scanned at',
                        'regular_price' => 'Regular price',
                        'reduced_price' => 'Reduced price',
                        'discount_amount' => 'Discount',
                        'discount_percent' => 'Discount %',
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
                    <td>{{ $markdown->product_id }}</td>
                    <td>{{ $markdown->category ?? '—' }}</td>
                    <td>{{ $markdown->scanned_at }}</td>
                    <td>{{ number_format($markdown->regular_price, 2) }} kr</td>
                    <td>{{ number_format($markdown->reduced_price, 2) }} kr</td>
                    <td>{{ number_format($markdown->discount_amount, 2) }} kr</td>
                    <td>{{ number_format($markdown->discount_percent, 1) }}%</td>
                </tr>
            @empty
                <tr><td colspan="7">No data found.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection