@extends('layouts.app')

@section('content')
<div class="login-page">
    <div class="login-card">
        <h1>Redigera butik</h1>
        <p class="login-subtitle">{{ $tenant->name }}</p>

        @if ($errors->any())
            <div class="form-errors">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('tenants.update', $tenant) }}" class="login-form">
            @csrf
            @method('PUT')

            <div class="filter-group">
                <label for="name">Butiksnamn</label>
                <input type="text" name="name" id="name" value="{{ old('name', $tenant->name) }}" required>
            </div>

            <div class="filter-group">
                <label for="password">Nytt lösenord</label>
                <input type="password" name="password" id="password" minlength="8">
                <small>Lämna tomt för att behålla nuvarande lösenord.</small>
            </div>

            <div class="filter-group">
                <label for="password_confirmation">Bekräfta nytt lösenord</label>
                <input type="password" name="password_confirmation" id="password_confirmation" minlength="8">
            </div>

            <button type="submit" class="filter-button login-button">Spara ändringar</button>
        </form>

        <a href="{{ route('tenants.select') }}" class="back-link">Tillbaka till butiker</a>
    </div>
</div>
@endsection