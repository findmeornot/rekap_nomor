<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Top Number') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/favicon.png.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="text-gray-900 antialiased" style="font-family: 'Manrope', sans-serif;">
        <div class="min-h-screen flex flex-col items-center justify-center px-4 py-10">
            <div>
                <a href="/">
                    <x-application-logo class="h-16 w-16 fill-current text-gray-700" />
                </a>
            </div>

            <div class="mt-6 w-full overflow-hidden rounded-2xl border border-gray-200 bg-white px-6 py-5 shadow-sm sm:max-w-md">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
