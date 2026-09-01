<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MD Totals</title>
    <link rel="stylesheet" href="{{ asset('css/statistics.css') }}">
    <head>
    <meta charset="UTF-8">
    <title>MD Totals</title>
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/navigation.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filters.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tables.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modal.css') }}">
    <link rel="stylesheet" href="{{ asset('css/compare.css') }}">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <link rel="stylesheet" href="{{ asset('css/loading.css') }}">
</head>
</head>
<body>
    <div class="container">
        @if (session()->has('tenant_id'))
            <nav class="main-nav">
                <a href="{{ route('statistics.index') }}">Statistik</a>
                <a href="{{ route('statistics.compare') }}">Jämför perioder</a>
            </nav>
        @endif
        @yield('content')
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Select2 from cdnjs.cloudflare.com -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script src="{{ asset('js/statistics.js') }}"></script>
</body>
</html>