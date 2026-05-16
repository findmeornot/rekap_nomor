@php
    $mainItems = [
        [
            'label' => 'Dashboard',
            'href' => route('dashboard'),
            'active' => request()->routeIs('dashboard'),
            'icon' => 'dashboard',
        ],
    ];

    $moduleItems = [];

    if (Auth::user()->isSuperAdmin()) {
        $moduleItems[] = [
            'label' => 'Manajemen User',
            'href' => route('superadmin.users.index'),
            'active' => request()->routeIs('superadmin.users.*') || request()->routeIs('superadmin.sub-leaders.*') || request()->routeIs('superadmin.leaders.*'),
            'icon' => 'users',
        ];
        $moduleItems[] = [
            'label' => 'Data Per Leader',
            'href' => route('superadmin.contacts.index'),
            'active' => request()->routeIs('superadmin.contacts.*'),
            'icon' => 'list',
        ];
    }

    if (Auth::user()->isLeader()) {
        $moduleItems[] = [
            'label' => 'Rekap Nomor',
            'href' => route('leader.contacts.index'),
            'active' => request()->routeIs('leader.contacts.*'),
            'icon' => 'list',
        ];
        $moduleItems[] = [
            'label' => 'Permintaan Nomor',
            'href' => route('leader.requests.index'),
            'active' => request()->routeIs('leader.requests.*'),
            'icon' => 'request',
        ];
    }

    if (Auth::user()->isSubLeader()) {
        $moduleItems[] = [
            'label' => 'Input Nomor',
            'href' => route('subleader.contacts.index'),
            'active' => request()->routeIs('subleader.*'),
            'icon' => 'phone',
        ];
    }
@endphp

<aside
    class="fixed inset-y-0 left-0 z-40 hidden border-r border-slate-200 bg-white px-3 py-6 transition-all duration-200 lg:flex lg:flex-col"
    :class="sidebarCollapsed ? 'lg:w-24' : 'lg:w-72'"
>
    <div class="mb-4 flex items-center justify-between px-3">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-3">
            <x-application-logo class="block h-8 w-auto fill-current text-blue-600" />
            <p x-show="!sidebarCollapsed" class="text-xl font-bold uppercase tracking-tight text-blue-600">Rekap Nomor</p>
        </a>

        <button
            type="button"
            class="hidden rounded-lg border border-slate-200 px-2 py-1 text-xs font-semibold text-slate-500 hover:bg-slate-100 lg:inline-flex"
            @click="sidebarCollapsed = !sidebarCollapsed; localStorage.setItem('sidebarCollapsed', sidebarCollapsed)"
            :title="sidebarCollapsed ? 'Expand sidebar' : 'Minimize sidebar'"
        >
            <span x-show="!sidebarCollapsed">&lt;&lt;</span>
            <span x-show="sidebarCollapsed">&gt;&gt;</span>
        </button>
    </div>

    <div class="scrollbar-thin flex-1 overflow-y-auto pr-1">
        <p x-show="!sidebarCollapsed" class="sidebar-section">Main</p>
        <nav class="mt-2 space-y-1">
            @foreach ($mainItems as $item)
                <a
                    href="{{ $item['href'] }}"
                    class="sidebar-link {{ $item['active'] ? 'sidebar-link-active' : '' }}"
                    :class="sidebarCollapsed ? 'justify-center px-2' : ''"
                    :title="sidebarCollapsed ? '{{ $item['label'] }}' : ''"
                >
                    @if ($item['icon'] === 'dashboard')
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 13h8V3H3zM13 21h8v-6h-8zM13 11h8V3h-8zM3 21h8v-6H3z"></path>
                        </svg>
                    @endif
                    <span x-show="!sidebarCollapsed">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        @if (! empty($moduleItems))
            <p x-show="!sidebarCollapsed" class="sidebar-section">Recapitulation</p>
            <nav class="mt-2 space-y-1">
                @foreach ($moduleItems as $item)
                    <a
                        href="{{ $item['href'] }}"
                        class="sidebar-link {{ $item['active'] ? 'sidebar-link-active' : '' }}"
                        :class="sidebarCollapsed ? 'justify-center px-2' : ''"
                        :title="sidebarCollapsed ? '{{ $item['label'] }}' : ''"
                    >
                        @if ($item['icon'] === 'users')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        @endif

                        @if ($item['icon'] === 'list')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="8" y1="6" x2="21" y2="6"></line>
                                <line x1="8" y1="12" x2="21" y2="12"></line>
                                <line x1="8" y1="18" x2="21" y2="18"></line>
                                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                <line x1="3" y1="18" x2="3.01" y2="18"></line>
                            </svg>
                        @endif

                        @if ($item['icon'] === 'phone')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.87 19.87 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.87 19.87 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.77.63 2.61a2 2 0 0 1-.45 2.11L8 9.91a16 16 0 0 0 6 6l1.47-1.29a2 2 0 0 1 2.11-.45c.84.3 1.71.51 2.61.63A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                        @endif

                        @if ($item['icon'] === 'request')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 5v14M5 12h14" />
                            </svg>
                        @endif

                        <span x-show="!sidebarCollapsed">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        @endif
    </div>

    <div x-show="!sidebarCollapsed" class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
        <p class="text-sm font-semibold text-slate-800">{{ Auth::user()->name }}</p>
        <p class="mb-3 text-xs text-slate-500">{{ Auth::user()->email }}</p>
        <div class="space-y-2">
            <a href="{{ route('profile.edit') }}" class="btn-subtle w-full text-sm">Profile</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-main w-full text-sm">Log Out</button>
            </form>
        </div>
    </div>

    <div x-show="sidebarCollapsed" class="mt-4 flex flex-col items-center gap-2">
        <a href="{{ route('profile.edit') }}" class="sidebar-link w-full justify-center px-2" title="Profile">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21a8 8 0 0 0-16 0"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
        </a>
        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button type="submit" class="sidebar-link w-full justify-center px-2" title="Log Out">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </button>
        </form>
    </div>
</aside>

<div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden" @click="sidebarOpen = false"></div>

<aside
    x-show="sidebarOpen"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="-translate-x-full opacity-0"
    x-transition:enter-end="translate-x-0 opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="translate-x-0 opacity-100"
    x-transition:leave-end="-translate-x-full opacity-0"
    class="fixed inset-y-0 left-0 z-50 w-72 border-r border-slate-200 bg-white px-3 py-6 shadow-2xl lg:hidden"
>
    <div class="mb-5 flex items-center justify-between px-3">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2" @click="sidebarOpen = false">
            <x-application-logo class="block h-8 w-auto fill-current text-blue-600" />
            <span class="text-sm font-bold uppercase text-blue-600">Rekap Nomor</span>
        </a>
        <button @click="sidebarOpen = false" class="rounded-lg border border-slate-200 px-2 py-1 text-xs font-semibold text-slate-500">X</button>
    </div>

    <p class="sidebar-section">Main</p>
    <nav class="mt-2 space-y-1">
        @foreach ($mainItems as $item)
            <a href="{{ $item['href'] }}" class="sidebar-link {{ $item['active'] ? 'sidebar-link-active' : '' }}" @click="sidebarOpen = false">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 13h8V3H3zM13 21h8v-6h-8zM13 11h8V3h-8zM3 21h8v-6H3z"></path>
                </svg>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    @if (! empty($moduleItems))
        <p class="sidebar-section">Recapitulation</p>
        <nav class="mt-2 space-y-1">
            @foreach ($moduleItems as $item)
                <a href="{{ $item['href'] }}" class="sidebar-link {{ $item['active'] ? 'sidebar-link-active' : '' }}" @click="sidebarOpen = false">
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    @endif
</aside>
