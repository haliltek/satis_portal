<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <title>@yield('title')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="{{ url('assets/panel/images/favicon.ico') }}">
    <link href="{{ url('assets/panel/css/bootstrap.min.css') }}" rel="stylesheet" />
    <link href="{{ url('assets/panel/css/icons.min.css') }}" rel="stylesheet" />
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background-color: #f5f7fa; }
        .account-pages { min-height: 100vh; padding: 40px 0; }
        .card { border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-radius: 8px; }
        .form-control { border-radius: 4px; }
        .btn-primary { background-color: #3498db; border-color: #3498db; }
        .btn-primary:hover { background-color: #2980b9; border-color: #2980b9; }
    </style>
</head>
<body>
    @yield('content')
    <script src="{{ url('assets/panel/libs/jquery/jquery.min.js') }}"></script>
    <script src="{{ url('assets/panel/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
