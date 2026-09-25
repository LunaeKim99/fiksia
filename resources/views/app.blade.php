<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <script>
        (function() {
            try {
                var stored = localStorage.getItem('fiksia-theme');
                var value = null;
                if (stored !== null) {
                    try {
                        value = JSON.parse(stored).mode;
                    } catch (error) {
                        value = null;
                    }
                }
                if (value !== 'light' && value !== 'dark' && value !== 'system') {
                    value = localStorage.getItem('appearance');
                }
                if (value !== 'light' && value !== 'dark' && value !== 'system') {
                    value = 'system';
                }
                var dark = value === 'dark' || (value === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', dark);
                document.documentElement.dataset.themeMode = value;
            } catch (error) {
                document.documentElement.classList.toggle('dark', window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.dataset.themeMode = 'system';
            }
        })();
        </script>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx'])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
