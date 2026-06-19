<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm text-slate-500">
            <span>Dashboard</span>
            <span>&gt;</span>
            <span class="font-semibold text-slate-900">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-wrap space-y-6">
            @if (session('success'))
                <div class="status-success fade-in-up">
                    {{ session('success') }}
                </div>
            @endif

            @if ($user->isSubLeader())
                <div class="panel fade-in-up">
                    <h3 class="text-4xl font-bold text-blue-600">Selamat Datang di Dashboard</h3>
                    <p class="mt-3 text-lg text-slate-600">Hai, {{ $user->name }}. Selamat mencari nomor!</p>
                    <p class="mt-6 text-base leading-relaxed text-slate-600">
                        Kamu bisa langsung mulai input data nomor dari menu di kiri. Semangat dan sukses untuk target hari ini.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('subleader.contacts.index') }}" class="btn-main">Mulai Input Nomor</a>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="panel fade-in-up">
                        <h3 class="section-title">Grafik Harian Asisten Marketing</h3>
                        <p class="section-subtitle">Jumlah input nomor yang kamu tambahkan setiap hari selama 7 hari terakhir, dengan garis target harian.</p>
                        <div class="mt-4">
                            <canvas id="subLeaderDailyChart" width="600" height="320"></canvas>
                        </div>
                    </div>
                </div>
            @elseif ($user->isLeader())
                <div class="stats-grid stagger {{ $user->isSpecialChannel() ? 'lg:grid-cols-2' : '' }}">
                    <div class="stat-card fade-in-up">
                        <p class="text-sm font-medium text-slate-500">Total Nomor</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['contacts'] }}</p>
                    </div>
                    <div class="stat-card fade-in-up">
                        <p class="text-sm font-medium text-slate-500">Total Sudah Dihubungi</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['contacted'] }}</p>
                    </div>
                    @if (!$user->isSpecialChannel())
                        <div class="stat-card fade-in-up">
                            <p class="text-sm font-medium text-slate-500">Total Asisten Marketing</p>
                            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['sub_leaders'] }}</p>
                        </div>
                    @endif
                </div>

                <div class="panel fade-in-up">
                    <h3 class="section-title">Sambutan Marketing Utama</h3>
                    <p class="section-subtitle">
                        Pantau progres tim kamu dari metrik utama. Tracking "sudah dihubungi" akan aktif setelah fitur follow-up ditambahkan.
                    </p>
                    <div class="mt-5 flex flex-wrap gap-3">
                        <a href="{{ route('leader.contacts.index') }}" class="btn-main">Lihat Rekap Nomor</a>
                        @if (!$user->isSpecialChannel())
                            <a href="{{ route('leader.requests.index') }}" class="btn-subtle">Permintaan Nomor</a>
                        @endif
                    </div>
                </div>

                @if ($user->isSpecialChannel())
                    <div class="panel fade-in-up">
                        <h3 class="section-title">Grafik Harian Marketing Utama</h3>
                        <p class="section-subtitle">Jumlah kontak yang sudah <strong>kamu</strong> hubungi per hari selama 7 hari terakhir, dengan garis target harian.</p>
                        <div class="mt-4">
                            <canvas id="mainDailyChart" width="800" height="320"></canvas>
                        </div>
                        <div class="mt-4 space-y-2 text-sm text-slate-700">
                            <p>Total nomor yang diinput asisten toploker: <strong>{{ $stats['contacts'] }}</strong></p>
                            <p>Sudah kamu hubungi: <strong>{{ $mainTargetData['contacted'] }}</strong></p>
                            <p>Sisa target: <strong>{{ $mainTargetData['remaining'] }}</strong></p>
                        </div>
                    </div>

                    <div class="panel fade-in-up">
                        <h3 class="section-title">Progres Target Sesama Marketing ({{ $user->marketingChannelLabel() }})</h3>
                        <p class="section-subtitle">Jumlah nomor yang dihubungi <strong>hari ini</strong> oleh masing-masing marketing utama channel {{ $user->marketingChannelLabel() }}, dibandingkan target harian {{ $user->getDailyTarget() }} nomor.</p>
                        <div class="mt-4" id="assistantProgressContainer">
                            <canvas id="assistantProgressChart" width="800" height="320"></canvas>
                        </div>
                    </div>
                @else
                    <div class="grid gap-6 lg:grid-cols-2">
                        <div class="panel fade-in-up">
                            <h3 class="section-title">Grafik Harian Marketing Utama</h3>
                            <p class="section-subtitle">Jumlah kontak yang sudah <strong>kamu</strong> hubungi per hari selama 7 hari terakhir, dengan garis target harian.</p>
                            <div class="mt-4">
                                <canvas id="mainDailyChart" width="600" height="320"></canvas>
                            </div>
                            <div class="mt-4 space-y-2 text-sm text-slate-700">
                                <p>Total kontak tim: <strong>{{ $stats['contacts'] }}</strong></p>
                                <p>Sudah kamu hubungi: <strong>{{ $mainTargetData['contacted'] }}</strong></p>
                                <p>Sisa target: <strong>{{ $mainTargetData['remaining'] }}</strong></p>
                            </div>
                        </div>

                        <div class="panel fade-in-up">
                            <h3 class="section-title">Grafik Harian Asisten Marketing</h3>
                            <p class="section-subtitle">Jumlah input nomor tim asisten marketing per hari, dengan garis target harian.</p>
                            <div class="mt-4">
                                <canvas id="assistantDailyChart" width="600" height="320"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="panel fade-in-up">
                        <h3 class="section-title">Progres Target Per Asisten Marketing</h3>
                        <p class="section-subtitle">Nomor yang diinput <strong>hari ini</strong> per asisten marketing dibandingkan target harian {{ \App\Models\User::TARGET_SUB_LEADER }} nomor.</p>
                        <div class="mt-4" id="assistantProgressContainer">
                            <canvas id="assistantProgressChart" width="800" height="320"></canvas>
                        </div>
                    </div>
                @endif
            @else
                <div class="stats-grid stagger lg:grid-cols-4">
                    <div class="stat-card fade-in-up">
                        <p class="text-sm font-medium text-slate-500">Total Marketing Utama</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['leaders'] }}</p>
                    </div>
                    <div class="stat-card fade-in-up">
                        <p class="text-sm font-medium text-slate-500">Total Asisten Marketing</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['sub_leaders'] }}</p>
                    </div>
                    <div class="stat-card fade-in-up">
                        <p class="text-sm font-medium text-slate-500">Total Nomor</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['contacts'] }}</p>
                    </div>
                    <div class="stat-card fade-in-up">
                        <p class="text-sm font-medium text-slate-500">Rata-rata / Asisten Marketing</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['avg_per_sub_leader'] }}</p>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="panel fade-in-up">
                        <h3 class="section-title">Ringkasan Sistem</h3>
                        <div class="mt-4 space-y-2 text-sm text-slate-700">
                            <p>
                                Marketing Utama teraktif:
                                <strong>{{ $meta['top_leader']?->name ?? '-' }}</strong>
                                ({{ $meta['top_leader']?->contacts_handled_count ?? 0 }} nomor)
                            </p>
                            <p>
                                Sub leader teraktif:
                                <strong>{{ $meta['top_sub_leader']?->name ?? '-' }}</strong>
                                ({{ $meta['top_sub_leader']?->contacts_entered_count ?? 0 }} nomor)
                            </p>
                        </div>
                    </div>

                    <div class="panel fade-in-up">
                        <h3 class="section-title">Aksi Cepat Superadmin</h3>
                        <p class="section-subtitle">Kelola struktur tim dan distribusi data dari satu tempat.</p>
                        <div class="mt-5 flex flex-wrap gap-3">
                            <a href="{{ route('superadmin.users.index') }}" class="btn-main">Kelola Marketing Utama &amp; Asisten Marketing</a>
                            <a href="{{ route('superadmin.contacts.index') }}" class="btn-subtle">Lihat Data Per Marketing Utama</a>
                        </div>
                    </div>

                    <div class="panel fade-in-up">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="section-title">Diagram Perbandingan Antar Marketing Utama (Toploker)</h3>
                                <p class="section-subtitle">Bandingkan performa leader Toploker berdasarkan jumlah nomor yang sudah dihubungi.</p>
                            </div>
                            <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
                                @foreach(request()->except('leader_chart_date') as $key => $value)
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endforeach
                                <label for="leader_chart_date" class="whitespace-nowrap text-sm font-medium text-slate-600">Pilih Tanggal</label>
                                <input type="date" id="leader_chart_date" name="leader_chart_date" value="{{ $leaderChartDate }}" 
                                    onchange="this.form.submit()"
                                    class="px-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            </form>
                        </div>
                        <div class="mt-4" id="superadminChartContainer">
                            <canvas id="leaderComparisonChart" width="600" height="320"></canvas>
                        </div>
                    </div>

                    <div class="panel fade-in-up">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="section-title">Diagram Perbandingan Antar Marketing Utama (Topmatch)</h3>
                                <p class="section-subtitle">Bandingkan performa leader Topmatch berdasarkan jumlah nomor yang sudah dihubungi.</p>
                            </div>
                            <span class="text-sm font-semibold text-slate-500 bg-slate-100 px-3 py-1 rounded-lg">Tanggal: {{ \Carbon\Carbon::parse($leaderChartDate)->format('d M Y') }}</span>
                        </div>
                        <div class="mt-4" id="topmatchChartContainer">
                            <canvas id="topmatchComparisonChart" width="600" height="320"></canvas>
                        </div>
                    </div>

                    <div class="panel fade-in-up">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="section-title">Diagram Perbandingan Antar Marketing Utama (Kerja Malam)</h3>
                                <p class="section-subtitle">Bandingkan performa leader Kerja Malam berdasarkan jumlah nomor yang sudah dihubungi.</p>
                            </div>
                            <span class="text-sm font-semibold text-slate-500 bg-slate-100 px-3 py-1 rounded-lg">Tanggal: {{ \Carbon\Carbon::parse($leaderChartDate)->format('d M Y') }}</span>
                        </div>
                        <div class="mt-4" id="kerjaMalamChartContainer">
                            <canvas id="kerjaMalamComparisonChart" width="600" height="320"></canvas>
                        </div>
                    </div>

                    <div class="panel fade-in-up">
                        <div>
                            <h3 class="section-title">Diagram Perbandingan Antar Tim</h3>
                            <p class="section-subtitle">Bandingkan performa tim berdasarkan Marketing Utama dan sub-leader di bawahnya.</p>
                        </div>
                        <div class="mt-4" id="teamComparisonChartContainer">
                            <canvas id="teamComparisonChart" width="600" height="320"></canvas>
                        </div>
                    </div>

                    <div class="panel fade-in-up">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="section-title">Diagram Perbandingan Antar Asisten Marketing</h3>
                                <p class="section-subtitle">Bandingkan total nomor, sudah dihubungi, dan belum dihubungi untuk setiap sub leader.</p>
                            </div>
                            <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
                                @foreach(request()->except('sub_leader_chart_date') as $key => $value)
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endforeach
                                <label for="sub_leader_chart_date" class="whitespace-nowrap text-sm font-medium text-slate-600">Pilih Tanggal</label>
                                <input type="date" id="sub_leader_chart_date" name="sub_leader_chart_date" value="{{ $subLeaderChartDate }}" 
                                    onchange="this.form.submit()"
                                    class="px-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            </form>
                        </div>
                        <div class="mt-4" id="superadminSubLeaderChartContainer">
                            <canvas id="subLeaderComparisonChart" width="600" height="320"></canvas>
                        </div>
                    </div>

                    <div class="panel fade-in-up">
                        <div>
                            <h3 class="section-title">Grafik Total Per Bulan</h3>
                            <p class="section-subtitle">Total nomor yang sudah dihubungi semua leader dan total nomor yang diinput semua sub leader per bulan (12 bulan terakhir).</p>
                        </div>
                        <div class="mt-4" id="monthlyTotalsChartContainer">
                            <canvas id="monthlyTotalsChart" width="600" height="320"></canvas>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if ($user->isSuperAdmin())
        <script id="superadmin-data" type="application/json">
            {!! json_encode([
                'leaderData' => $leaderComparisonData ?? [],
                'topmatchData' => $topmatchComparisonData ?? [],
                'kerjaMalamData' => $kerjaMalamComparisonData ?? [],
                'subLeaderData' => $subLeaderComparisonData ?? [],
                'teamComparisonData' => $teamComparisonData ?? [],
                'monthlyTotalsData' => $monthlyTotalsData ?? [],
                'leaderChartDate' => $leaderChartDate ?? '',
                'subLeaderChartDate' => $subLeaderChartDate ?? ''
            ]) !!}
        </script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Chart.register(ChartDataLabels);

                const dataStore = JSON.parse(document.getElementById('superadmin-data').textContent);
                const leaderData = dataStore.leaderData;
                const topmatchData = dataStore.topmatchData;
                const kerjaMalamData = dataStore.kerjaMalamData;
                const subLeaderData = dataStore.subLeaderData;
                const teamComparisonData = dataStore.teamComparisonData;
                const monthlyTotalsData = dataStore.monthlyTotalsData;
                const leaderMonthDisplay = new Date(dataStore.leaderChartDate).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' });
                const subLeaderMonthDisplay = new Date(dataStore.subLeaderChartDate).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' });

                const renderComparisonChart = (canvasId, containerId, data, title) => {
                    const canvas = document.getElementById(canvasId);
                    const container = document.getElementById(containerId);

                    if (!canvas || !container) {
                        return;
                    }

                    if (!data.length) {
                        container.innerHTML = '<p class="text-slate-500">Tidak ada data untuk bulan ini.</p>';
                        return;
                    }

                    new Chart(canvas, {
                        type: 'bar',
                        data: {
                            labels: data.map(item => item.label),
                            datasets: [
                                {
                                    label: 'Total Nomor',
                                    data: data.map(item => item.total),
                                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                                    borderColor: 'rgba(59, 130, 246, 1)',
                                    borderWidth: 1,
                                },
                                {
                                    label: 'Sudah Dihubungi',
                                    data: data.map(item => item.contacted),
                                    backgroundColor: 'rgba(34, 197, 94, 0.5)',
                                    borderColor: 'rgba(34, 197, 94, 1)',
                                    borderWidth: 1,
                                },
                                {
                                    label: 'Belum Dihubungi',
                                    data: data.map(item => item.uncontacted),
                                    backgroundColor: 'rgba(248, 113, 113, 0.5)',
                                    borderColor: 'rgba(239, 68, 68, 1)',
                                    borderWidth: 1,
                                },
                            ],
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                title: {
                                    display: true,
                                    text: title,
                                    font: {
                                        size: 14,
                                        weight: '500',
                                    },
                                    padding: {
                                        bottom: 20,
                                    },
                                },
                                datalabels: {
                                    color: '#1e3a8a',
                                    backgroundColor: 'rgba(219, 234, 254, 0.95)',
                                    borderColor: 'rgba(147, 197, 253, 1)',
                                    borderWidth: 1,
                                    borderRadius: 6,
                                    padding: {
                                        top: 2,
                                        right: 6,
                                        bottom: 2,
                                        left: 6,
                                    },
                                    anchor: 'end',
                                    align: 'end',
                                    offset: 6,
                                    clamp: true,
                                    clip: false,
                                    display: (context) => context.datasetIndex === 0 && Number(context.dataset.data[context.dataIndex]) > 0,
                                    formatter: (value) => Number(value).toLocaleString('id-ID'),
                                    font: {
                                        weight: '600',
                                        size: 10,
                                    },
                                },
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1,
                                    },
                                },
                            },
                        },
                    });
                };

                const renderLeaderComparisonChart = (canvasId, containerId, data, title) => {
                    const canvas = document.getElementById(canvasId);
                    const container = document.getElementById(containerId);

                    if (!canvas || !container) {
                        return;
                    }

                    if (!data.length) {
                        container.innerHTML = '<p class="text-slate-500">Tidak ada data untuk bulan ini.</p>';
                        return;
                    }

                    new Chart(canvas, {
                        type: 'bar',
                        data: {
                            labels: data.map(item => item.label),
                            datasets: [
                                {
                                    label: 'Sudah Dihubungi',
                                    data: data.map(item => item.contacted),
                                    backgroundColor: 'rgba(34, 197, 94, 0.7)',
                                    borderColor: 'rgba(16, 185, 129, 1)',
                                    borderWidth: 1,
                                },
                            ],
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                title: {
                                    display: true,
                                    text: title,
                                    font: {
                                        size: 14,
                                        weight: '500',
                                    },
                                    padding: {
                                        bottom: 20,
                                    },
                                },
                                datalabels: {
                                    color: '#1e3a8a',
                                    backgroundColor: 'rgba(219, 234, 254, 0.95)',
                                    borderColor: 'rgba(147, 197, 253, 1)',
                                    borderWidth: 1,
                                    borderRadius: 6,
                                    padding: {
                                        top: 2,
                                        right: 6,
                                        bottom: 2,
                                        left: 6,
                                    },
                                    anchor: 'end',
                                    align: 'end',
                                    offset: 6,
                                    clamp: true,
                                    clip: false,
                                    display: (context) => context.datasetIndex === 0 && Number(context.dataset.data[context.dataIndex]) > 0,
                                    formatter: (value) => Number(value).toLocaleString('id-ID'),
                                    font: {
                                        weight: '600',
                                        size: 10,
                                    },
                                },
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1,
                                    },
                                },
                            },
                        },
                    });
                };

                renderLeaderComparisonChart(
                    'leaderComparisonChart',
                    'superadminChartContainer',
                    leaderData,
                    'Perbandingan Marketing Utama (Toploker) ' + ' (' + leaderMonthDisplay + ')'
                );

                renderLeaderComparisonChart(
                    'topmatchComparisonChart',
                    'topmatchChartContainer',
                    topmatchData,
                    'Perbandingan Marketing Utama (Topmatch) ' + ' (' + leaderMonthDisplay + ')'
                );

                renderLeaderComparisonChart(
                    'kerjaMalamComparisonChart',
                    'kerjaMalamChartContainer',
                    kerjaMalamData,
                    'Perbandingan Marketing Utama (Kerja Malam) ' + ' (' + leaderMonthDisplay + ')'
                );

                renderComparisonChart(
                    'teamComparisonChart',
                    'teamComparisonChartContainer',
                    teamComparisonData,
                    'Perbandingan Tim'
                );

                renderComparisonChart(
                    'subLeaderComparisonChart',
                    'superadminSubLeaderChartContainer',
                    subLeaderData,
                    'Perbandingan Asisten Marketing' + ' (' + subLeaderMonthDisplay + ')'
                );

                const monthlyCanvas = document.getElementById('monthlyTotalsChart');
                const monthlyContainer = document.getElementById('monthlyTotalsChartContainer');

                if (monthlyCanvas && monthlyContainer) {
                    if (!monthlyTotalsData.length) {
                        monthlyContainer.innerHTML = '<p class="text-slate-500">Belum ada data bulanan untuk ditampilkan.</p>';
                    } else {
                        new Chart(monthlyCanvas, {
                            type: 'bar',
                            data: {
                                labels: monthlyTotalsData.map(item => item.label),
                                datasets: [
                                    {
                                        label: 'Total Sudah Dihubungi (Semua Leader)',
                                        data: monthlyTotalsData.map(item => item.contacted_total),
                                        backgroundColor: 'rgba(34, 197, 94, 0.5)',
                                        borderColor: 'rgba(34, 197, 94, 1)',
                                        borderWidth: 1,
                                    },
                                    {
                                        label: 'Total Input Nomor (Semua Sub Leader)',
                                        data: monthlyTotalsData.map(item => item.input_total),
                                        backgroundColor: 'rgba(59, 130, 246, 0.5)',
                                        borderColor: 'rgba(59, 130, 246, 1)',
                                        borderWidth: 1,
                                    },
                                ],
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    title: {
                                        display: true,
                                        text: 'Tren Total Bulanan (12 Bulan Terakhir)',
                                        font: {
                                            size: 14,
                                            weight: '500',
                                        },
                                        padding: {
                                            bottom: 20,
                                        },
                                    },
                                    datalabels: {
                                        color: '#0f172a',
                                        backgroundColor: 'rgba(255, 255, 255, 0.9)',
                                        borderColor: 'rgba(203, 213, 225, 1)',
                                        borderWidth: 1,
                                        borderRadius: 6,
                                        padding: {
                                            top: 2,
                                            right: 6,
                                            bottom: 2,
                                            left: 6,
                                        },
                                        anchor: 'end',
                                        align: 'end',
                                        offset: 4,
                                        clamp: true,
                                        clip: false,
                                        display: (context) => Number(context.dataset.data[context.dataIndex]) > 0,
                                        formatter: (value) => Number(value).toLocaleString('id-ID'),
                                        font: {
                                            weight: '600',
                                            size: 10,
                                        },
                                    },
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            stepSize: 1,
                                        },
                                    },
                                },
                            },
                        });
                    }
                }
            });
        </script>
    @endif

    @if ($user->isLeader() || $user->isSubLeader() || $user->isSuperAdmin())
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    @endif

    @if ($user->isLeader() || $user->isSubLeader())
        <script id="leader-data" type="application/json">
            {!! json_encode([
                'mainTargetData' => $mainTargetData ?? [],
                'mainDailyData' => $mainDailyData ?? [],
                'mainDailyTargetData' => $mainDailyTargetData ?? [],
                'assistantDailyData' => $assistantDailyData ?? [],
                'assistantDailyTargetData' => $assistantDailyTargetData ?? [],
                'subLeaderDailyData' => $subLeaderDailyData ?? [],
                'subLeaderDailyTargetData' => $subLeaderDailyTargetData ?? [],
                'dailyLabels' => $stats['daily_labels'] ?? [],
                'assistantChartData' => $assistantChartData ?? [],
                'assistantTarget' => $user->isSpecialChannel() ? $user->getDailyTarget() : \App\Models\User::TARGET_SUB_LEADER,
                'isSpecialChannel' => $user->isSpecialChannel(),
            ]) !!}
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const dataStore = JSON.parse(document.getElementById('leader-data').textContent);
                const mainTargetData = dataStore.mainTargetData;
                const mainDailyData = dataStore.mainDailyData;
                const mainDailyTargetData = dataStore.mainDailyTargetData;
                const assistantDailyData = dataStore.assistantDailyData;
                const assistantDailyTargetData = dataStore.assistantDailyTargetData;
                const subLeaderDailyData = dataStore.subLeaderDailyData;
                const subLeaderDailyTargetData = dataStore.subLeaderDailyTargetData;
                const dailyLabels = dataStore.dailyLabels;

                const renderBarChart = (canvasId, chartTitle, labels, datasets) => {
                    const canvas = document.getElementById(canvasId);
                    if (!canvas) {
                        return;
                    }

                    new Chart(canvas, {
                        type: 'bar',
                        data: {
                            labels,
                            datasets,
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                title: {
                                    display: true,
                                    text: chartTitle,
                                    font: {
                                        size: 14,
                                        weight: '500',
                                    },
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function (context) {
                                            return context.dataset.label + ': ' + context.parsed.y.toLocaleString('id-ID');
                                        },
                                    },
                                },
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 10,
                                    },
                                },
                            },
                        },
                    });
                };

                if (dailyLabels.length && mainDailyData.length) {
                    renderBarChart('mainDailyChart', 'Grafik Harian Marketing Utama', dailyLabels, [
                        {
                            label: 'Kontak Dihubungi',
                            data: mainDailyData,
                            backgroundColor: 'rgba(34, 197, 94, 0.75)',
                            borderColor: 'rgba(34, 197, 94, 1)',
                            borderWidth: 1,
                        },
                        {
                            label: 'Target Harian',
                            data: mainDailyTargetData,
                            backgroundColor: 'rgba(59, 130, 246, 0.35)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1,
                        },
                    ]);
                }

                if (dailyLabels.length && assistantDailyData.length) {
                    renderBarChart('assistantDailyChart', 'Grafik Harian Asisten Marketing', dailyLabels, [
                        {
                            label: 'Input Harian',
                            data: assistantDailyData,
                            backgroundColor: 'rgba(16, 185, 129, 0.75)',
                            borderColor: 'rgba(5, 150, 105, 1)',
                            borderWidth: 1,
                        },
                        {
                            label: 'Target Harian',
                            data: assistantDailyTargetData,
                            backgroundColor: 'rgba(59, 130, 246, 0.35)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1,
                        },
                    ]);
                }

                if (dailyLabels.length && subLeaderDailyData.length) {
                    renderBarChart('subLeaderDailyChart', 'Grafik Harian Asisten Marketing', dailyLabels, [
                        {
                            label: 'Input Harian',
                            data: subLeaderDailyData,
                            backgroundColor: 'rgba(34, 197, 94, 0.75)',
                            borderColor: 'rgba(34, 197, 94, 1)',
                            borderWidth: 1,
                        },
                        {
                            label: 'Target Harian',
                            data: subLeaderDailyTargetData,
                            backgroundColor: 'rgba(59, 130, 246, 0.35)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1,
                        },
                    ]);
                }

                const assistantChartData = dataStore.assistantChartData;
                const assistantTarget = dataStore.assistantTarget;
                const assistantProgressCanvas = document.getElementById('assistantProgressChart');
                const assistantProgressContainer = document.getElementById('assistantProgressContainer');

                if (assistantProgressCanvas && assistantProgressContainer) {
                    if (!assistantChartData.length) {
                        assistantProgressContainer.innerHTML = dataStore.isSpecialChannel
                            ? '<p class="text-slate-500 text-sm">Belum ada data marketing di channel ini.</p>'
                            : '<p class="text-slate-500 text-sm">Belum ada asisten marketing di tim ini.</p>';
                    } else {
                        new Chart(assistantProgressCanvas, {
                            type: 'bar',
                            data: {
                                labels: assistantChartData.map(d => d.label),
                                datasets: [
                                    {
                                        label: dataStore.isSpecialChannel ? 'Total Dihubungi' : 'Total Input Nomor',
                                        data: assistantChartData.map(d => d.count),
                                        backgroundColor: assistantChartData.map(d =>
                                            d.count >= assistantTarget
                                                ? 'rgba(34, 197, 94, 0.75)'
                                                : 'rgba(59, 130, 246, 0.65)'
                                        ),
                                        borderColor: assistantChartData.map(d =>
                                            d.count >= assistantTarget
                                                ? 'rgba(22, 163, 74, 1)'
                                                : 'rgba(37, 99, 235, 1)'
                                        ),
                                        borderWidth: 1,
                                    },
                                    {
                                        label: 'Target (' + assistantTarget + ')',
                                        data: assistantChartData.map(() => assistantTarget),
                                        type: 'line',
                                        borderColor: 'rgba(239, 68, 68, 0.8)',
                                        borderWidth: 2,
                                        borderDash: [6, 3],
                                        pointRadius: 0,
                                        fill: false,
                                        tension: 0,
                                    },
                                ],
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: { display: true },
                                    tooltip: {
                                        callbacks: {
                                            label: function (context) {
                                                if (context.datasetIndex === 0) {
                                                    const val = context.parsed.y;
                                                    const pct = assistantTarget > 0
                                                        ? Math.round((val / assistantTarget) * 100)
                                                        : 0;
                                                    return 'Input: ' + val.toLocaleString('id-ID') + ' (' + pct + '% target)';
                                                }
                                                return 'Target: ' + context.parsed.y.toLocaleString('id-ID');
                                            },
                                        },
                                    },
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: { stepSize: 25 },
                                    },
                                },
                            },
                        });
                    }
                }
            });
        </script>
    @endif
</x-app-layout>
