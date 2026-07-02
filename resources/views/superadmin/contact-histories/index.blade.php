<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold leading-tight text-slate-900">
                History Kontak
            </h2>
            <p class="mt-1 text-sm text-slate-600">Arsip kontak bulanan yang sudah diarsipkan. Data bersifat <strong>read-only</strong>.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-wrap space-y-6">

            {{-- Filter Panel --}}
            <div class="panel fade-in-up relative z-20">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="section-title">Filter History</h3>
                        <p class="section-subtitle">Cari berdasarkan nama, nomor, periode arsip, atau pengelola.</p>
                    </div>
                </div>

                <form
                    method="GET"
                    class="mt-5 space-y-4"
                    x-data="{
                        archivePeriod: '{{ $filters['archive_period'] ?? '' }}',
                        leaderId: '{{ $filters['leader_id'] ?? '' }}',
                        subLeaderId: '{{ $filters['sub_leader_id'] ?? '' }}',
                        perPage: '{{ (string) ($filters['per_page'] ?? 20) }}',
                        periodOptions: [{ id: '', name: 'Semua Periode' }, ...@js($archivePeriods->map(fn ($p) => ['id' => $p, 'name' => \Carbon\Carbon::createFromFormat('Y-m', $p)->format('F Y')])->values())],
                        leaderOptions: [{ id: '', name: 'Semua Marketing Utama' }, ...@js($leaders->map(fn ($l) => ['id' => (string) $l->id, 'name' => $l->name])->values())],
                        subLeaderOptions: [{ id: '', name: 'Semua Asisten Marketing' }, ...@js($subLeaders->map(fn ($l) => ['id' => (string) $l->id, 'name' => $l->name])->values())],
                        perPageOptions: [
                            { id: '10', name: '10 / halaman' },
                            { id: '20', name: '20 / halaman' },
                            { id: '50', name: '50 / halaman' },
                            { id: '100', name: '100 / halaman' },
                        ],
                        optionLabel(options, id, fallback) {
                            const found = options.find((item) => item.id === id);
                            return found ? found.name : fallback;
                        },
                    }"
                >
                    <input type="hidden" name="archive_period" :value="archivePeriod">
                    <input type="hidden" name="leader_id" :value="leaderId">
                    <input type="hidden" name="sub_leader_id" :value="subLeaderId">
                    <input type="hidden" name="per_page" :value="perPage">

                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-12">
                        <input
                            type="search"
                            name="q"
                            value="{{ $filters['q'] ?? '' }}"
                            placeholder="Cari nama atau nomor..."
                            autocomplete="off"
                            aria-label="Cari history kontak"
                            class="xl:col-span-3"
                        />

                        {{-- Archive Period filter --}}
                        <div class="filter-dropdown xl:col-span-3" x-data="{ open: false }">
                            <button
                                type="button"
                                class="filter-trigger"
                                @click="open = !open"
                                @keydown.escape.window="open = false"
                                @click.outside="open = false"
                                aria-label="Pilih periode arsip"
                            >
                                <span x-text="optionLabel(periodOptions, archivePeriod, 'Semua Periode')"></span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition class="filter-menu">
                                <template x-for="option in periodOptions" :key="`period-${option.id || 'all'}`">
                                    <button
                                        type="button"
                                        class="filter-option"
                                        :class="{ 'filter-option-active': option.id === archivePeriod }"
                                        @click="archivePeriod = option.id; open = false"
                                    >
                                        <span x-text="option.name"></span>
                                        <svg x-show="option.id === archivePeriod" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.2 7.2a1 1 0 01-1.415 0l-3-3a1 1 0 011.415-1.415l2.293 2.293 6.493-6.493a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </template>
                            </div>
                        </div>

                        {{-- Leader filter --}}
                        <div class="filter-dropdown xl:col-span-2" x-data="{ open: false }">
                            <button
                                type="button"
                                class="filter-trigger"
                                @click="open = !open"
                                @keydown.escape.window="open = false"
                                @click.outside="open = false"
                                aria-label="Pilih Marketing Utama"
                            >
                                <span x-text="optionLabel(leaderOptions, leaderId, 'Semua Marketing Utama')"></span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition class="filter-menu">
                                <template x-for="option in leaderOptions" :key="`leader-${option.id || 'all'}`">
                                    <button
                                        type="button"
                                        class="filter-option"
                                        :class="{ 'filter-option-active': option.id === leaderId }"
                                        @click="leaderId = option.id; open = false"
                                    >
                                        <span x-text="option.name"></span>
                                        <svg x-show="option.id === leaderId" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.2 7.2a1 1 0 01-1.415 0l-3-3a1 1 0 011.415-1.415l2.293 2.293 6.493-6.493a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </template>
                            </div>
                        </div>

                        {{-- Sub-Leader filter --}}
                        <div class="filter-dropdown xl:col-span-2" x-data="{ open: false }">
                            <button
                                type="button"
                                class="filter-trigger"
                                @click="open = !open"
                                @keydown.escape.window="open = false"
                                @click.outside="open = false"
                                aria-label="Pilih Asisten Marketing"
                            >
                                <span x-text="optionLabel(subLeaderOptions, subLeaderId, 'Semua Asisten Marketing')"></span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition class="filter-menu">
                                <template x-for="option in subLeaderOptions" :key="`sub-${option.id || 'all'}`">
                                    <button
                                        type="button"
                                        class="filter-option"
                                        :class="{ 'filter-option-active': option.id === subLeaderId }"
                                        @click="subLeaderId = option.id; open = false"
                                    >
                                        <span x-text="option.name"></span>
                                        <svg x-show="option.id === subLeaderId" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.2 7.2a1 1 0 01-1.415 0l-3-3a1 1 0 011.415-1.415l2.293 2.293 6.493-6.493a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </template>
                            </div>
                        </div>

                        {{-- Per page --}}
                        <div class="filter-dropdown xl:col-span-2" x-data="{ open: false }">
                            <button
                                type="button"
                                class="filter-trigger"
                                @click="open = !open"
                                @keydown.escape.window="open = false"
                                @click.outside="open = false"
                                aria-label="Jumlah per halaman"
                            >
                                <span x-text="optionLabel(perPageOptions, perPage, '20 / halaman')"></span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition class="filter-menu">
                                <template x-for="option in perPageOptions" :key="`pp-${option.id}`">
                                    <button
                                        type="button"
                                        class="filter-option"
                                        :class="{ 'filter-option-active': option.id === perPage }"
                                        @click="perPage = option.id; open = false"
                                    >
                                        <span x-text="option.name"></span>
                                        <svg x-show="option.id === perPage" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.2 7.2a1 1 0 01-1.415 0l-3-3a1 1 0 011.415-1.415l2.293 2.293 6.493-6.493a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="btn-main">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            Cari
                        </button>
                        @if (array_filter($filters))
                            <a href="{{ route('superadmin.history.index') }}" class="btn-subtle">Reset</a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Results Panel --}}
            <div class="panel fade-in-up">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="section-title">Hasil History</h3>
                        <p class="section-subtitle">
                            Menampilkan {{ $histories->firstItem() ?? 0 }}–{{ $histories->lastItem() ?? 0 }}
                            dari {{ number_format($histories->total()) }} rekod arsip.
                        </p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                        Read-Only
                    </span>
                </div>

                @if ($histories->isEmpty())
                    <div class="flex flex-col items-center justify-center py-16 text-center">
                        <svg class="mb-4 h-12 w-12 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <polyline points="1 4 1 10 7 10"></polyline>
                            <path d="M3.51 15a9 9 0 1 0 .49-4.5"></path>
                        </svg>
                        <p class="text-slate-500">Belum ada data history yang ditemukan.</p>
                        @if (array_filter($filters))
                            <p class="mt-1 text-xs text-slate-400">Coba ubah atau hapus filter pencarian.</p>
                        @endif
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <th class="pb-3 pr-4">Nama</th>
                                    <th class="pb-3 pr-4">Nomor</th>
                                    <th class="pb-3 pr-4">Marketing Utama</th>
                                    <th class="pb-3 pr-4">Asisten Marketing</th>
                                    <th class="pb-3 pr-4">Status</th>
                                    <th class="pb-3 pr-4">Periode Arsip</th>
                                    <th class="pb-3">Diarsipkan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($histories as $history)
                                    <tr class="group hover:bg-slate-50 transition-colors">
                                        <td class="py-3 pr-4">
                                            <span class="font-medium text-slate-800">
                                                {{ $history->contact_name ?? '—' }}
                                            </span>
                                        </td>
                                        <td class="py-3 pr-4">
                                            {{-- Phone number with text-selection disabled for privacy --}}
                                            <span class="font-mono text-xs text-slate-600 select-none" style="user-select:none;-webkit-user-select:none;">
                                                {{ $history->phone }}
                                            </span>
                                        </td>
                                        <td class="py-3 pr-4 text-slate-700">
                                            {{ $history->leader?->name ?? '—' }}
                                        </td>
                                        <td class="py-3 pr-4 text-slate-700">
                                            {{ $history->subLeader?->name ?? '—' }}
                                        </td>
                                        <td class="py-3 pr-4">
                                            @if ($history->is_contacted)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                    Sudah Dihubungi
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-500 ring-1 ring-slate-200">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                                    Belum Dihubungi
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-3 pr-4">
                                            <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700 ring-1 ring-blue-200">
                                                {{ \Carbon\Carbon::createFromFormat('Y-m', $history->archive_period)->format('F Y') }}
                                            </span>
                                        </td>
                                        <td class="py-3 text-xs text-slate-500">
                                            {{ $history->archived_at?->format('d M Y H:i') ?? '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if ($histories->hasPages())
                        <div class="mt-6 border-t border-slate-100 pt-4">
                            {{ $histories->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <style>
        /* Prevent text selection on phone column for privacy */
        .no-select {
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }
    </style>
</x-app-layout>
