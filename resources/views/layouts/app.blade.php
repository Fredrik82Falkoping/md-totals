<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>MD Totals</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/navigation.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filters.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tables.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modal.css') }}">
    <link rel="stylesheet" href="{{ asset('css/compare.css') }}">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <link rel="stylesheet" href="{{ asset('css/loading.css') }}">
    <link rel="stylesheet" href="{{ asset('css/import-logs.css') }}">
</head>
<body>
    <nav class="main-nav">
        <a class="brand-link" href="https://www.eswinne.se" aria-label="Företagets hemsida">
            <img src="{{ asset('img/logo-duo.svg') }}" alt="Duo">
        </a>
        @if (auth()->check())
            <div class="main-nav-links">
                @if (session()->has('tenant_id'))
                <a href="{{ route('statistics.index') }}">Statistik</a>
                <a href="{{ route('statistics.compare') }}">Jämför perioder</a>
                @endif
                @if (auth()->user()->is_admin)
                    <a href="{{ route('tenants.select') }}">Byt butik</a>
                    <a href="{{ route('import-logs.index') }}">API-importer</a>
                    @if (session()->has('tenant_id'))
                        <a href="{{ route('tenants.edit', session('tenant_id')) }}">Redigera butik</a>
                    @endif
                @endif
            </div>
            <form method="POST" action="{{ route('logout') }}" class="logout-form">
                @csrf
                <button type="submit">Logga ut</button>
            </form>
        @endif
    </nav>
    <div class="container">
        @yield('content')
    </div>

    <footer class="site-footer">
        <div class="site-footer-inner">
            <a href="mailto:petra.vulevity@eswinne.se">petra.vulevity@eswinne.se</a>
            <span>© eSwinne</span>
            <a href="tel:+46739349595">+46 73 934 95 95</a>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Select2 from cdnjs.cloudflare.com -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script src="{{ asset('js/statistics.js') }}"></script>
</body>
</html>