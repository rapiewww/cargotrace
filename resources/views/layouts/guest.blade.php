<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'CargoTrace — Global Logistics Intelligence' }}</title>

    <!-- Scripts & Styles via Vite (Plus Jakarta Sans, JetBrains Mono, Leaflet bundled) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="bg-[#050B14] text-slate-100 font-sans antialiased min-h-full flex flex-col selection:bg-amber-500 selection:text-slate-950">
    @yield('content')

    @stack('scripts')
</body>
</html>
