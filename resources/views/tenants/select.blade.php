@extends('layouts.app')

@section('content')
<h1>Select store</h1>

<form method="POST" action="{{ route('tenants.store') }}">
    <label for="week">Week:</label>
    <select name="week" id="week">
        <option value="">All weeks</option>
        @foreach ($weeks as $week)
            <option value="{{ $week }}" @selected($currentWeek === $week)>
                {{ $week }}
            </option>
        @endforeach
    </select>
    
    @csrf
    <select name="tenant_id" required>
        <option value="">-- Choose a store --</option>
        @foreach ($tenants as $tenant)
            <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
        @endforeach
    </select>
    <button type="submit">Continue</button>
</form>
@endsection