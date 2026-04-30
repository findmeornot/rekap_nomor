<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Top Number') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700|space+grotesk:500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased" style="font-family: 'Manrope', sans-serif;">
        <div
            x-data="{
                sidebarOpen: false,
                sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
                accountOpen: false,
                darkTheme: localStorage.getItem('theme') === 'dark',
                applyTheme() {
                    document.documentElement.classList.toggle('theme-dark', this.darkTheme);
                    localStorage.setItem('theme', this.darkTheme ? 'dark' : 'light');
                }
            }"
            x-init="applyTheme()"
            class="min-h-screen"
        >
            @include('layouts.navigation')

            <div class="transition-all duration-200" :class="sidebarCollapsed ? 'lg:pl-24' : 'lg:pl-72'">
                <div class="border-b border-slate-200 bg-white">
                    <div class="page-wrap flex h-16 items-center justify-between">
                        <button
                            @click="sidebarOpen = true"
                            class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm lg:hidden"
                        >
                            Menu
                        </button>
                        <div class="hidden lg:block"></div>
                        <div class="relative flex items-center gap-3">
                            <button
                                class="icon-badge"
                                @click="darkTheme = !darkTheme; applyTheme()"
                                :title="darkTheme ? 'Pakai tema terang' : 'Pakai tema gelap'"
                            >
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="4"></circle>
                                    <path d="M12 2v2m0 16v2m10-10h-2M4 12H2m17.07-7.07l-1.41 1.41M6.34 17.66l-1.41 1.41m0-14.14l1.41 1.41m10.73 10.73l1.41 1.41"></path>
                                </svg>
                            </button>
                            <button
                                class="icon-badge font-semibold"
                                @click="accountOpen = !accountOpen"
                                @keydown.escape.window="accountOpen = false"
                            >
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </button>

                            <div
                                x-show="accountOpen"
                                x-transition
                                @click.outside="accountOpen = false"
                                class="absolute right-0 top-14 z-50 w-56 rounded-2xl border border-slate-200 bg-white p-3 shadow-lg"
                            >
                                <p class="text-sm font-semibold text-slate-800">{{ Auth::user()->name }}</p>
                                <p class="mb-3 text-xs text-slate-500">{{ Auth::user()->email }}</p>
                                <a href="{{ route('profile.edit') }}" class="btn-subtle mb-2 w-full text-sm">Profile</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="btn-main w-full text-sm">Log Out</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Page Heading -->
                @isset($header)
                    <header class="page-wrap pt-6">
                        <div class="surface px-5 py-5 sm:px-6">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main class="pb-10">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
