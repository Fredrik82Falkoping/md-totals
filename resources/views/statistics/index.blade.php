<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MD Totals — Statistics</title>
    <style>
        body { font-family: sans-serif; margin: 2rem; }
        .summary { display: flex; gap: 2rem; margin-bottom: 2rem; }
        .summary div { background: #f4f4f4; padding: 1rem 1.5rem; border-radius: 8px; }
        .summary strong { display: block; font-size: 1.5rem; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 0.5rem; text-align: left; font-size: 0.9rem; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
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

    <h2>Latest markdowns</h2>
    <table>
        <thead>
            <tr>
                <th>Product ID</th>
                <th>Category</th>
                <th>Scanned at</th>
                <th>Regular price</th>
                <th>Reduced price</th>
                <th>Discount</th>
                <th>Discount %</th>
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
</body>
</html>