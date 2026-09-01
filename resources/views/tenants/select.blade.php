@extends('layouts.app')

@section('content')
<div class="login-page">
    <div class="login-card">
        <h1>MD Totals</h1>
        <p class="login-subtitle">Välj din butik för att fortsätta</p>

        <form method="POST" action="{{ route('tenants.store') }}" class="login-form">
            @csrf

            <div class="filter-group">
                <label for="tenant_id">Butik</label>
                <select name="tenant_id" id="tenant_id" required>
                    <option value="">-- Välj en butik --</option>
                    @foreach ($tenants as $tenant)
                        <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="filter-button login-button">Fortsätt</button>
        </form>
    </div>
</div>
@endsection