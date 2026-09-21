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

            @if ($user)
                <div class="filter-group">
                    <label>Butikens användare</label>
                    <p>{{ $user->username ?: $user->name }}</p>
                    <small>Det finns redan en användare för butiken.</small>
                </div>
            @else
                <div class="filter-group">
                    <p>Butiken har ingen användare än. Skapa en ny användare för butiken.</p>
                    <label for="username">Användarnamn</label>
                    <input type="text" name="username" id="username" value="{{ old('username') }}" required>
                </div>
            @endif

            <div class="filter-group">
                <label for="password">{{ $user ? 'Nytt lösenord' : 'Lösenord' }}</label>
                <input type="password" name="password" id="password" minlength="4" {{ $user ? '' : 'required' }}>
                @if ($user)
                    <small>Lämna tomt för att behålla nuvarande lösenord.</small>
                @endif
            </div>

            <div class="filter-group">
                <label for="password_confirmation">Bekräfta nytt lösenord</label>
                <input type="password" name="password_confirmation" id="password_confirmation" minlength="4">
            </div>

            <button type="submit" class="filter-button login-button">Spara ändringar</button>
        </form>

        <a href="{{ route('tenants.select') }}" class="back-link">Tillbaka till butiker</a>
    </div>
</div>
@endsection