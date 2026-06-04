<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold leading-tight text-slate-900">
                Rekap Data Per Marketing Utama
            </h2>
            <p class="mt-1 text-sm text-slate-600">Super admin dapat melihat semua data nomor berdasarkan leader.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-wrap space-y-6">
            <div class="panel fade-in-up">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="section-title">Filter Leader</h3>
                        <p class="section-subtitle">Cari data lebih cepat dengan filter leader, status, kata kunci, dan rentang tanggal.</p>
                    </div>
                </div>

                <form
                    method="GET"
                    class="mt-5 space-y-4"
                    x-data="{
                        period: '{{ $filters['period'] ?? 'all' }}',
                        leaderId: '{{ $selectedLeaderId ?? '' }}',
                        status: '{{ $uiFilters['status'] ?? 'all' }}',
                        perPage: '{{ (string) ($uiFilters['per_page'] ?? 20) }}',
                        leaderOptions: [{ id: '', name: 'Semua Leader' }, ...@js($leaders->map(fn ($leader) => ['id' => (string) $leader->id, 'name' => $leader->name])->values())],
                        statusOptions: [
                            { id: 'all', name: 'Semua Status' },
                            { id: 'contacted', name: 'Sudah Dihubungi' },
                            { id: 'uncontacted', name: 'Belum Dihubungi' },
                        ],
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
                    <input type="hidden" name="period" :value="period">
                    <input type="hidden" name="leader_id" :value="leaderId">
                    <input type="hidden" name="status" :value="status">
                    <input type="hidden" name="per_page" :value="perPage">

                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-12">
                        <input
                            type="search"
                            name="q"
                            value="{{ $uiFilters['q'] ?? '' }}"
                            placeholder="Cari leader, sub leader, nama, nomor..."
                            autocomplete="off"
                            aria-label="Cari data kontak"
                            class="xl:col-span-3"
                        />
                        <div class="filter-dropdown xl:col-span-3" x-data="{ open: false }">
                            <button
                                type="button"
                                class="filter-trigger"
                                @click="open = !open"
                                @keydown.escape.window="open = false"
                                @click.outside="open = false"
                                aria-label="Pilih leader"
                            >
                                <span x-text="optionLabel(leaderOptions, leaderId, 'Semua Leader')"></span>
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
                        <div class="filter-dropdown xl:col-span-2" x-data="{ open: false }">
                            <button
                                type="button"
                                class="filter-trigger"
                                @click="open = !open"
                                @keydown.escape.window="open = false"
                                @click.outside="open = false"
                                aria-label="Pilih status"
                            >
                                <span x-text="optionLabel(statusOptions, status, 'Semua Status')"></span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition class="filter-menu">
                                <template x-for="option in statusOptions" :key="`status-${option.id}`">
                                    <button
                                        type="button"
                                        class="filter-option"
                                        :class="{ 'filter-option-active': option.id === status }"
                                        @click="status = option.id; open = false"
                                    >
                                        <span x-text="option.name"></span>
                                        <svg x-show="option.id === status" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.2 7.2a1 1 0 01-1.415 0l-3-3a1 1 0 011.415-1.415l2.293 2.293 6.493-6.493a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div class="filter-dropdown xl:col-span-2" x-data="{ open: false }">
                            <button
                                type="button"
                                class="filter-trigger"
                                @click="open = !open"
                                @keydown.escape.window="open = false"
                                @click.outside="open = false"
                                aria-label="Pilih jumlah per halaman"
                            >
                                <span x-text="optionLabel(perPageOptions, perPage, '20 / halaman')"></span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition class="filter-menu">
                                <template x-for="option in perPageOptions" :key="`per-page-${option.id}`">
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
                        <div class="flex items-center gap-2 xl:col-span-2">
                            <button type="submit" class="btn-main w-full whitespace-nowrap">Terapkan</button>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-medium text-slate-600">Periode:</span>
                        <button type="button" class="chip" :class="period === 'all' ? 'border-blue-200 bg-blue-50 text-blue-700' : ''" @click="period = 'all'">Semua Data</button>
                        <button type="button" class="chip" :class="period === '7d' ? 'border-blue-200 bg-blue-50 text-blue-700' : ''" @click="period = '7d'">7 Hari</button>
                        <button type="button" class="chip" :class="period === '30d' ? 'border-blue-200 bg-blue-50 text-blue-700' : ''" @click="period = '30d'">1 Bulan</button>
                        <button type="button" class="chip" :class="period === 'custom' ? 'border-blue-200 bg-blue-50 text-blue-700' : ''" @click="period = 'custom'">Range Tanggal</button>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2 xl:max-w-3xl" x-show="period === 'custom'" x-cloak>
                        <input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}" aria-label="Tanggal mulai" />
                        <input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}" aria-label="Tanggal akhir" />
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @if ($selectedLeaderId || ($filters['period'] ?? 'all') !== 'all' || ($filters['start_date'] ?? null) || ($filters['end_date'] ?? null) || ($uiFilters['q'] ?? null) || ($uiFilters['status'] ?? 'all') !== 'all' || ($uiFilters['per_page'] ?? 20) !== 20)
                            <a href="{{ route('superadmin.contacts.index') }}" class="btn-subtle">Reset Semua Filter</a>
                        @endif
                    </div>
                </form>

                @if ($selectedLeaderId)
                    <p class="mt-3 text-sm font-medium text-slate-600">
                        Sedang menampilkan data:
                        <span class="text-slate-900">
                            {{ $leaders->firstWhere('id', $selectedLeaderId)?->name ?? '-' }}
                        </span>
                    </p>
                @endif
                @if (($filters['period'] ?? 'all') !== 'all' || ($filters['start_date'] ?? null) || ($filters['end_date'] ?? null))
                    <p class="mt-1 text-sm font-medium text-slate-600">
                        Filter tanggal:
                        @if (($filters['period'] ?? 'all') === '7d')
                            <span class="text-slate-900">7 Hari Terakhir</span>
                        @elseif (($filters['period'] ?? 'all') === '30d')
                            <span class="text-slate-900">1 Bulan Terakhir</span>
                        @elseif (($filters['period'] ?? 'all') === 'custom')
                            <span class="text-slate-900">{{ $filters['start_date'] ?? '-' }} s/d {{ $filters['end_date'] ?? '-' }}</span>
                        @else
                            <span class="text-slate-900">Custom</span>
                        @endif
                    </p>
                @endif
                <p class="mt-1 text-sm font-medium text-slate-600">
                    Total ditemukan:
                    <span class="text-slate-900">{{ number_format($contacts->total()) }} data</span>
                </p>
            </div>

            <div class="panel fade-in-up">
                <h3 class="section-title">Ringkasan Per Leader</h3>
                <div class="table-wrap mt-4">
                    <table class="table-clean w-full text-sm">
                        <thead>
                            <tr class="text-slate-600 text-xs uppercase tracking-wide">
                                <th class="text-left py-2">No</th>
                                <th class="text-left py-2">Leader</th>
                                <th class="text-right py-2">Sudah Dihubungi (Hari Ini)</th>
                                <th class="text-right py-2">Dihubungi Bulan Ini</th>
                                <th class="text-left py-2">Input Terakhir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y bg-white">
                            @forelse ($summaryLeaders as $leader)
                                <tr class="hover:bg-slate-50">
                                    <td class="py-3">{{ $leaderNumberMap[$leader->id] ?? $loop->iteration }}</td>
                                    <td class="py-3 font-medium">{{ $leader->name }}</td>
                                    <td class="py-3 text-right">
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 border border-emerald-200 px-3 py-1 text-xs font-semibold text-emerald-700">
                                            {{ number_format($leader->contacted_contacts_count) }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-right">{{ number_format($leader->contacted_contacts_monthly_count) }}</td>
                                    <td class="py-3 text-left text-slate-600">{{ $leader->contacts_as_leader_max_created_at ? \Carbon\Carbon::parse($leader->contacts_as_leader_max_created_at)->format('d M Y H:i') : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-4 text-slate-500">Data leader tidak ditemukan untuk filter ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel fade-in-up">
                <h3 class="section-title">Semua Nomor Per Leader</h3>
                <p class="section-subtitle">Menampilkan seluruh data nomor yang sudah diinput.</p>

                <div class="table-wrap mt-4">
                    <table class="table-clean">
                        <thead>
                            <tr>
                                <th>Leader</th>
                                <th>Sub Leader</th>
                                <th>Nama Kontak</th>
                                <th>Nomor</th>
                                <th>Status</th>
                                <th>Tanggal Input</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($contacts as $contact)
                                <tr>
                                    <td>{{ $contact->leader?->name ?? '-' }}</td>
                                    <td>{{ $contact->subLeader?->name ?? '-' }}</td>
                                    <td>{{ $contact->contact_name ?? '-' }}</td>
                                    <td class="font-medium">{{ $contact->phone }}</td>
                                    <td>
                                        @if ($contact->isContacted())
                                            <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                                Sudah Dihubungi
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full border border-slate-300 bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                                Belum Dihubungi
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $contact->created_at->format('d M Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-4 text-slate-500">Belum ada data nomor.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $contacts->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
