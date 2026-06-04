<x-app-layout>
    <div id="toast-container" class="fixed right-4 top-4 z-50 flex flex-col gap-2 items-end pointer-events-none" style="position:fixed;top:1rem;right:1rem;z-index:9999;"></div>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold leading-tight text-slate-900">
                Rekap Nomor Leader
            </h2>
            <p class="mt-1 text-sm text-slate-600">Pantau semua input nomor dari sub leader di bawah kamu.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-wrap space-y-6">
            <div class="panel fade-in-up">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h3 class="section-title">Sub Leader Saya</h3>
                        <p class="section-subtitle">Pilih assistant marketing untuk melihat data yang lebih spesifik.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('leader.contacts.export', request()->only('assistant_marketing_id', 'period', 'start_date', 'end_date', 'q', 'status')) }}" class="btn-main">Export CSV</a>
                    </div>
                </div>

                <form method="GET" class="mt-4 space-y-3" x-data="{ period: '{{ $filters['period'] ?? 'all' }}' }">
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                        <input
                            type="search"
                            name="q"
                            value="{{ $uiFilters['q'] ?? '' }}"
                            placeholder="Cari nama, nomor, sub leader..."
                            autocomplete="off"
                            aria-label="Cari data kontak"
                            class="xl:col-span-2"
                        />
                        <select name="assistant_marketing_id" aria-label="Pilih assistant marketing">
                            <option value="">Semua Assistant Marketing</option>
                            @foreach ($subLeaders as $subLeader)
                                <option value="{{ $subLeader->id }}" @selected($selectedAssistantMarketingId === $subLeader->id)>
                                    {{ $subLeader->name }}
                                </option>
                            @endforeach
                        </select>
                        {{-- Status filter removed: status controlled only by checkbox on each row --}}
                        <select name="per_page" aria-label="Jumlah data per halaman">
                            <option value="10" @selected(($uiFilters['per_page'] ?? 20) === 10)>10 / halaman</option>
                            <option value="20" @selected(($uiFilters['per_page'] ?? 20) === 20)>20 / halaman</option>
                            <option value="50" @selected(($uiFilters['per_page'] ?? 20) === 50)>50 / halaman</option>
                            <option value="100" @selected(($uiFilters['per_page'] ?? 20) === 100)>100 / halaman</option>
                        </select>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @php
                            $baseQuery = request()->except(['period', 'start_date', 'end_date', 'page']);
                        @endphp
                        <a href="{{ route('leader.contacts.index', array_merge($baseQuery, ['period' => 'all'])) }}" class="chip {{ ($filters['period'] ?? 'all') === 'all' ? 'border-blue-200 bg-blue-50 text-blue-700' : '' }}">Semua Data</a>
                        <a href="{{ route('leader.contacts.index', array_merge($baseQuery, ['period' => '7d'])) }}" class="chip {{ ($filters['period'] ?? '') === '7d' ? 'border-blue-200 bg-blue-50 text-blue-700' : '' }}">7 Hari</a>
                        <a href="{{ route('leader.contacts.index', array_merge($baseQuery, ['period' => '30d'])) }}" class="chip {{ ($filters['period'] ?? '') === '30d' ? 'border-blue-200 bg-blue-50 text-blue-700' : '' }}">1 Bulan</a>
                        <button type="button" class="chip" @click="period = 'custom'">Range Tanggal</button>
                    </div>

                    <div class="grid gap-3 md:grid-cols-3" x-show="period === 'custom' || '{{ $filters['period'] ?? 'all' }}' === 'custom'">
                        <input type="hidden" name="period" :value="period" />
                        <input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}" aria-label="Tanggal mulai" />
                        <input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}" aria-label="Tanggal akhir" />
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="btn-main">Terapkan</button>
                        @if ($selectedAssistantMarketingId || ($filters['period'] ?? 'all') !== 'all' || ($filters['start_date'] ?? null) || ($filters['end_date'] ?? null) || ($uiFilters['q'] ?? null) || ($uiFilters['status'] ?? 'all') !== 'all' || ($uiFilters['per_page'] ?? 20) !== 20)
                            <a href="{{ route('leader.contacts.index') }}" class="btn-subtle">Reset Semua Filter</a>
                        @endif
                    </div>
                </form>

                <div class="mt-4 flex flex-wrap gap-2">
                    <a
                        href="{{ route('leader.contacts.index', request()->only('period', 'start_date', 'end_date')) }}"
                        class="chip {{ $selectedAssistantMarketingId ? '' : 'border-blue-200 bg-blue-50 text-blue-700' }}"
                    >
                        Semua Assistant Marketing
                    </a>
                    @forelse ($subLeaders as $subLeader)
                        <a
                            href="{{ route('leader.contacts.index', array_merge(request()->only('period', 'start_date', 'end_date'), ['assistant_marketing_id' => $subLeader->id])) }}"
                            class="chip {{ $selectedAssistantMarketingId === $subLeader->id ? 'border-blue-200 bg-blue-50 text-blue-700' : '' }}"
                        >
                            {{ $subLeader->name }} ({{ $subLeader->contacts_entered_count }} nomor)
                        </a>
                    @empty
                        <p class="text-sm text-slate-500">Belum ada sub leader yang ditugaskan.</p>
                    @endforelse
                </div>
            </div>

            <div class="panel fade-in-up">
                <h3 class="section-title">Ringkasan Leader</h3>
                <div class="stats-grid mt-4">
                    <div class="stat-card">
                        <p class="text-sm font-medium text-slate-500">Total Kontak Marketing Utama</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($totalContactsCount) }}</p>
                    </div>
                    <div class="stat-card">
                        <p class="text-sm font-medium text-slate-500">Total Sudah Dihubungi</p>
                        <p id="total-contacted-count" class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($totalContactedCount) }}</p>
                    </div>
                    <div class="stat-card">
                        <p class="text-sm font-medium text-slate-500">Dihubungi Bulan Ini</p>
                        <p id="contacted-this-month-count" class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($contactedThisMonthCount) }}</p>
                    </div>
                    <div class="stat-card">
                        <p class="text-sm font-medium text-slate-500">Target Marketing Utama</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($target) }}</p>
                        <p class="mt-1 text-xs text-slate-500">Progress: <span id="target-progress">{{ $progress }}</span>%</p>
                    </div>
                </div>
            </div>

            <div class="panel fade-in-up">
                <h3 class="section-title">Daftar Nomor</h3>
                <p class="section-subtitle">Total ditemukan: <strong>{{ number_format($contacts->total()) }}</strong> data.</p>
                @if ($selectedAssistantMarketingId)
                    <p class="section-subtitle">
                        Menampilkan data dari:
                        <strong>{{ $subLeaders->firstWhere('id', $selectedAssistantMarketingId)?->name ?? '-' }}</strong>
                    </p>
                @endif
                @if (($filters['period'] ?? 'all') !== 'all' || ($filters['start_date'] ?? null) || ($filters['end_date'] ?? null))
                    <p class="section-subtitle">
                        Filter tanggal:
                        @if (($filters['period'] ?? 'all') === '7d')
                            <strong>7 Hari Terakhir</strong>
                        @elseif (($filters['period'] ?? 'all') === '30d')
                            <strong>1 Bulan Terakhir</strong>
                        @elseif (($filters['period'] ?? 'all') === 'custom')
                            <strong>{{ $filters['start_date'] ?? '-' }} s/d {{ $filters['end_date'] ?? '-' }}</strong>
                        @else
                            <strong>Custom</strong>
                        @endif
                    </p>
                @endif
                <div class="table-wrap mt-4">
                    <table class="table-clean">
                        <thead>
                            <tr>
                                <th>Nama Kontak</th>
                                <th>Nomor</th>
                                <th>Input Oleh</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($contacts as $contact)
                                <tr>
                                    <td>{{ $contact->contact_name ?? '-' }}</td>
                                    <td class="font-medium">{{ $contact->phone }}</td>
                                    <td>{{ $contact->subLeader?->name ?? '-' }}</td>
                                    <td>
                                        <span class="contact-status-label">{{ $contact->statusLabel() }}</span>
                                    </td>
                                    <td>{{ $contact->created_at->format('d M Y H:i') }}</td>
                                    <td>
                                        <input
                                            type="checkbox"
                                            class="contact-marked"
                                            data-url="{{ route('leader.contacts.status', $contact) }}"
                                            data-contact-id="{{ $contact->id }}"
                                            data-status-updated-at="{{ optional($contact->status_updated_at)->format('Y-m') }}"
                                            aria-label="Tandai sudah dihubungi"
                                            @checked($contact->isContacted())
                                        />
                                    </td>
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

            <div class="panel fade-in-up">
                <h3 class="section-title">Grafik Tracking Per Bulan</h3>
                <p class="section-subtitle">Jumlah nomor yang sudah dihubungi per bulan.</p>
                <div class="mt-4">
                    <div id="chartContainer">Loading chart...</div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        const data = @json($monthlyContactedData);

        const container = document.getElementById('chartContainer');
        if (container) {
            if (data.length === 0) {
                container.innerHTML = 'Tidak ada data untuk ditampilkan.';
            } else {
                const canvas = document.createElement('canvas');
                canvas.width = 400;
                canvas.height = 200;
                container.innerHTML = '';
                container.appendChild(canvas);

                new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: data.map(item => item.label),
                        datasets: [{
                            label: 'Nomor Dihubungi',
                            data: data.map(item => item.count),
                            backgroundColor: 'rgba(59, 130, 246, 0.5)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const toastContainer = document.getElementById('toast-container');
        const totalContactedEl = document.getElementById('total-contacted-count');
        const contactedThisMonthEl = document.getElementById('contacted-this-month-count');
        const targetProgressEl = document.getElementById('target-progress');
        const currentMonth = new Date().toISOString().slice(0, 7);

        const parseInteger = (element) => {
            if (!element) {
                return 0;
            }

            return parseInt(element.textContent.replace(/[^0-9]/g, ''), 10) || 0;
        };

        let totalContactedCount = parseInteger(totalContactedEl);
        let contactedThisMonthCount = parseInteger(contactedThisMonthEl);
        let progressValue = parseInteger(targetProgressEl);

        const formatNumber = (value) => new Intl.NumberFormat('id-ID').format(value);

        const showToast = (message, type) => {
            if (!toastContainer) {
                return;
            }

            const toast = document.createElement('div');
            toast.className = `rounded-lg px-4 py-3 text-sm font-semibold shadow-lg transition-all pointer-events-auto ${type === 'success' ? 'bg-emerald-500 text-white' : 'bg-red-600 text-white'}`;
            toast.textContent = message;
            toastContainer.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        };

        const updateSummaryCounts = (deltaTotal, deltaMonth, deltaProgress) => {
            totalContactedCount += deltaTotal;
            contactedThisMonthCount += deltaMonth;
            progressValue += deltaProgress;

            if (totalContactedEl) {
                totalContactedEl.textContent = formatNumber(Math.max(totalContactedCount, 0));
            }

            if (contactedThisMonthEl) {
                contactedThisMonthEl.textContent = formatNumber(Math.max(contactedThisMonthCount, 0));
            }

            if (targetProgressEl) {
                targetProgressEl.textContent = String(Math.max(progressValue, 0));
            }
        };

        document.querySelectorAll('.contact-marked').forEach((checkbox) => {
            checkbox.addEventListener('change', async (ev) => {
                const cb = ev.currentTarget;
                const url = cb.dataset.url;
                const row = cb.closest('tr');
                const statusLabel = row?.querySelector('.contact-status-label');
                const previousChecked = !cb.checked;
                const previousStatusUpdatedAt = cb.dataset.statusUpdatedAt || '';
                const currentMonthMatch = previousStatusUpdatedAt === currentMonth ? 1 : 0;
                const nextChecked = cb.checked;

                cb.disabled = true;

                try {
                    const response = await fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ is_contacted: nextChecked }),
                    });

                    if (!response.ok) {
                        throw new Error('Gagal memperbarui status');
                    }

                    const payload = await response.json();

                    if (!payload.ok) {
                        throw new Error('Gagal memperbarui status');
                    }

                    if (statusLabel) {
                        statusLabel.textContent = payload.label ?? (nextChecked ? 'Sudah Dihubungi' : 'Belum Dihubungi');
                    }

                    if (nextChecked && !previousChecked) {
                        updateSummaryCounts(1, 1, 1);
                        cb.dataset.statusUpdatedAt = currentMonth;
                    }

                    if (!nextChecked && previousChecked) {
                        updateSummaryCounts(-1, -currentMonthMatch, -1);
                        cb.dataset.statusUpdatedAt = '';
                    }

                    showToast('Status berhasil disimpan.', 'success');
                } catch (error) {
                    cb.checked = previousChecked;
                    showToast('Gagal memperbarui status. Silakan coba lagi.', 'error');
                } finally {
                    cb.disabled = false;
                }
            });
        });
    </script>
</x-app-layout>
