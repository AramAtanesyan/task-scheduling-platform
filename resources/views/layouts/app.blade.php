<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    @stack('meta')
    
    <title>@yield('title', 'Task Scheduling Platform')</title>
    
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    
    @stack('styles')
    
    @yield('head-styles')
</head>
<body>
    @yield('content')
    
    @stack('scripts')
</body>
</html>

