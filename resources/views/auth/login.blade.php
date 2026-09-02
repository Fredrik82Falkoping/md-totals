@extends('layouts.app')

@section('content')
<div class="login-page">
    <div class="login-card">
        <h1>MD Totals</h1>
        <p class="login-subtitle">Logga in för att fortsätta</p>

        @if ($errors->any())
            <div class="login-errors" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="login-form">
            @csrf

            <div class="filter-group">
                <label for="username">Användarnamn</label>
                <input type="text" name="username" id="username" value="{{ old('username') }}" required autofocus autocomplete="username">
            </div>

            <div class="filter-group">
                <label for="password">Lösenord</label>
                <input type="password" name="password" id="password" required autocomplete="current-password">
            </div>

            <label class="remember-label">
                <input type="checkbox" name="remember" value="1">
                Kom ihåg mig
            </label>

            <button type="submit" class="filter-button login-button">Logga in</button>
        </form>
    </div>
</div>
@endsection
