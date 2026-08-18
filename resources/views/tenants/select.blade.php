@extends('layouts.app')

@section('content')
<h1>Select store</h1>

<form method="POST" action="{{ route('tenants.store') }}">
    @csrf
    <select name="tenant_id" required>
        <option value="">-- Välj en butik --</option>
        @foreach ($tenants as $tenant)
            <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
        @endforeach
    </select>
    <button type="submit">Fortsätt</button>
</form>
@endsection