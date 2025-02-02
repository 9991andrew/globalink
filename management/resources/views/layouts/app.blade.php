<!DOCTYPE html>
<html class="" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <link rel="apple-touch-icon" sizes="57x57" href="/apple-icon-57x57.png">
        <link rel="apple-touch-icon" sizes="60x60" href="/apple-icon-60x60.png">
        <link rel="apple-touch-icon" sizes="72x72" href="/apple-icon-72x72.png">
        <link rel="apple-touch-icon" sizes="76x76" href="/apple-icon-76x76.png">
        <link rel="apple-touch-icon" sizes="114x114" href="/apple-icon-114x114.png">
        <link rel="apple-touch-icon" sizes="120x120" href="/apple-icon-120x120.png">
        <link rel="apple-touch-icon" sizes="144x144" href="/apple-icon-144x144.png">
        <link rel="apple-touch-icon" sizes="152x152" href="/apple-icon-152x152.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-icon-180x180.png">
        <link rel="icon" type="image/png" sizes="192x192"  href="/android-icon-192x192.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="manifest" href="/manifest.json">
        <meta name="msapplication-TileColor" content="#ffffff">
        <meta name="msapplication-TileImage" content="/ms-icon-144x144.png">
        <meta name="theme-color" content="#ffffff">

        <title>{{ config('app.name', 'MEGA World Management') }}</title>

        <!-- Styles -->
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
        @livewireStyles
        @stack('styles')

        <!-- Scripts -->
        <script>
            // Add inline in `head` to avoid FOUC
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.querySelector('html').classList.add('dark');
            } else {
                document.querySelector('html').classList.remove('dark');
            }
        </script>

        @stack('scripts')
        <script src="{{ asset('js/app.js') }}" defer></script>

    </head>
    <body class="bg-gray-200 dark:bg-black text-gray-800 dark:text-gray-300 overflow-x-hidden">
            @auth
                @include('layouts.navigation')
            @endauth
            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
            <script>
                function toggleDarkMode() {
                    let htmlClasses = document.querySelector('html').classList;
                    if (htmlClasses.contains('dark')) {
                        htmlClasses.remove('dark');
                        localStorage.theme = 'light'
                    }
                    else {
                        htmlClasses.add('dark');
                        localStorage.theme = 'dark'
                    }
                }
                // document.getElementById('switchTheme').addEventListener('click', function() {
                //     toggleDarkMode();
                // });
            </script>
        @livewireScripts
    </body>
</html>
