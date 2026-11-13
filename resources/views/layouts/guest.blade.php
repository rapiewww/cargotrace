<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="map-tile-url" content="{{ config('cargotrace.map.tile_url') }}">
    <meta name="map-tile-attribution" content="{{ config('cargotrace.map.attribution') }}">

    <title>{{ $title ?? 'CargoTrace: Global Logistics Intelligence' }}</title>

    <!-- Theme Initialization Script (Zero-Flicker, prefers-color-scheme fallback) -->
    <script>
        (function() {
            let theme = localStorage.getItem('cargotrace_theme');
            if (!theme) {
                theme = (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) ? 'light' : 'dark';
            }
            document.documentElement.setAttribute('data-theme', theme);
            if (theme === 'light') {
                document.documentElement.classList.add('light');
                document.documentElement.classList.remove('dark');
            } else {
                document.documentElement.classList.add('dark');
                document.documentElement.classList.remove('light');
            }
        })();
    </script>

    <!-- Scripts & Styles via Vite (Plus Jakarta Sans, JetBrains Mono, Leaflet bundled) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="theme-page font-sans antialiased min-h-full flex flex-col selection:bg-blue-600 selection:text-white transition-colors duration-200">
    @yield('content')

    @stack('scripts')
</body>
</html>
