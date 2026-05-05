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
            @elseif ($user->isLeader())
                <div class="stats-grid stagger">
                    <div class="stat-card fade-in-up">
                        <p class="text-sm font-medium text-slate-500">Total Nomor</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['contacts'] }}</p>
                    </div>
                    <div class="stat-card fade-in-up">
                        <p class="text-sm font-medium text-slate-500">Total Sudah Dihubungi</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['contacted'] }}</p>
                    </div>
                    <div class="stat-card fade-in-up">
                        <p class="text-sm font-medium text-slate-500">Total Sub Leader</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['sub_leaders'] }}</p>
                    </div>
                </div>

                <div class="panel fade-in-up">
                    <h3 class="section-title">Sambutan Leader</h3>
                    <p class="section-subtitle">
                        Pantau progres tim kamu dari metrik utama. Tracking "sudah dihubungi" akan aktif setelah fitur follow-up ditambahkan.
                    </p>
                    <div class="mt-5 flex flex-wrap gap-3">
                        <a href="{{ route('leader.contacts.index') }}" class="btn-main">Lihat Rekap Nomor</a>
                    </div>
                </div>
            @else
                <div class="stats-grid stagger lg:grid-cols-4">
                    <div class="stat-card fade-in-up">
                        <p class="text-sm font-medium text-slate-500">Total Leader</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['leaders'] }}</p>
                    </div>
                    <div class="stat-card fade-in-up">
                        <p class="text-sm font-medium text-slate-500">Total Sub Leader</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['sub_leaders'] }}</p>
                    </div>
                    <div class="stat-card fade-in-up">
                        <p class="text-sm font-medium text-slate-500">Total Nomor</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['contacts'] }}</p>
                    </div>
                    <div class="stat-card fade-in-up">
                        <p class="text-sm font-medium text-slate-500">Rata-rata / Sub Leader</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['avg_per_sub_leader'] }}</p>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="panel fade-in-up">
                        <h3 class="section-title">Ringkasan Sistem</h3>
                        <div class="mt-4 space-y-2 text-sm text-slate-700">
                            <p>Sub leader belum punya leader: <strong>{{ $meta['sub_leaders_without_leader'] ?? 0 }}</strong></p>
                            <p>
                                Leader teraktif:
                                <strong>{{ $meta['top_leader']?->name ?? '-' }}</strong>
                                ({{ $meta['top_leader']?->contacts_as_leader_count ?? 0 }} nomor)
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
                            <a href="{{ route('superadmin.users.index') }}" class="btn-main">Kelola Leader &amp; Sub Leader</a>
                            <a href="{{ route('superadmin.contacts.index') }}" class="btn-subtle">Lihat Data Per Leader</a>
                        </div>
                    </div>

                    <div class="panel fade-in-up">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="section-title">Grafik Kontak Dihubungi per Leader</h3>
                                <p class="section-subtitle">Lihat performa setiap leader berdasarkan jumlah kontak yang sudah dihubungi.</p>
                            </div>
                            <form method="GET" action="{{ route('dashboard') }}" class="flex items-end gap-2">
                                <div>
                                    <label for="leader_chart_month" class="block text-xs font-medium text-slate-600 mb-1">Pilih Bulan</label>
                                    <input type="month" id="leader_chart_month" name="leader_chart_month" value="{{ $selectedMonth }}" 
                                        class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                                </div>
                                <button type="submit" class="px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                                    Filter
                                </button>
                            </form>
                        </div>
                        <div class="mt-4" id="superadminChartContainer">
                            <canvas id="leaderContactedChart" width="600" height="320"></canvas>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if ($user->isSuperAdmin())
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const data = @json($leaderContactedData ?? []);
                const selectedMonth = @json($selectedMonth);
                const canvas = document.getElementById('leaderContactedChart');

                if (!canvas || !data.length) {
                    if (canvas) {
                        canvas.parentElement.innerHTML = '<p class="text-slate-500">Tidak ada data untuk bulan ini.</p>';
                    }
                    return;
                }

                const monthDisplay = new Date(selectedMonth + '-01').toLocaleDateString('id-ID', { 
                    year: 'numeric', 
                    month: 'long' 
                });

                new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: data.map(item => item.label),
                        datasets: [{
                            label: 'Kontak Dihubungi',
                            data: data.map(item => item.count),
                            backgroundColor: 'rgba(34, 197, 94, 0.5)',
                            borderColor: 'rgba(34, 197, 94, 1)',
                            borderWidth: 1,
                        }],
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Data Bulan ' + monthDisplay,
                                font: {
                                    size: 14,
                                    weight: '500',
                                },
                                padding: {
                                    bottom: 20,
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
            });
        </script>
    @endif
</x-app-layout>
