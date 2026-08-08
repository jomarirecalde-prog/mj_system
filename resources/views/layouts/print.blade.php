<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Print')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body>
@yield('content')
<script>
    window.addEventListener('load', function () {
        if (@json(!request()->boolean('preview'))) {
            window.print();
        }
    });
</script>
@stack('scripts')
</body>
</html>
