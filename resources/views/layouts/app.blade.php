<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MD Totals</title>
    <link rel="stylesheet" href="{{ asset('css/statistics.css') }}">
</head>
<body>
    <div class="container">
        @yield('content')
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="{{ asset('js/statistics.js') }}"></script>
</body>
</html>