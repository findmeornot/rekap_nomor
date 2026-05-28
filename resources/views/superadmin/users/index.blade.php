<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-semibold leading-tight text-slate-900">
                    Manajemen User
                </h2>
                <p class="mt-1 text-sm text-slate-600">Kelola tim, Marketing Utama, dan Asisten Marketing.</p>
            </div>
            <a href="{{ route('superadmin.contacts.index') }}" class="btn-main">
                Lihat Data Per Marketing Utama
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-wrap space-y-6">
            @if (session('success'))
                <div class="status-success fade-in-up">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="status-error fade-in-up">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="panel fade-in-up">
                    <h3 class="section-title">Tambah Marketing Utama</h3>
                    <form class="mt-4 space-y-4" method="POST" action="{{ route('superadmin.leaders.store') }}">
                        @csrf
                        <div>
                            <x-input-label for="leader_name" value="Nama" />
                            <x-text-input id="leader_name" name="name" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <x-input-label for="leader_email" value="Email" />
                            <x-text-input id="leader_email" type="email" name="email" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <x-input-label for="leader_password" value="Password" />
                            <x-text-input id="leader_password" type="password" name="password" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <x-input-label for="leader_team_id" value="Pilih Tim" />
                            <select id="leader_team_id" name="team_id" class="mt-1 block w-full" required>
                                <option value="">-- Pilih Tim --</option>
                                @foreach ($teams as $team)
                                    <option value="{{ $team->id }}">{{ $team->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <x-primary-button>Simpan Marketing Utama</x-primary-button>
                    </form>
                </div>

                <div class="panel fade-in-up">
                    <h3 class="section-title">Tambah Assistant Marketing</h3>
                    <form class="mt-4 space-y-4" method="POST" action="{{ route('superadmin.sub-leaders.store') }}">
                        @csrf
                        <div>
                            <x-input-label for="sub_name" value="Nama" />
                            <x-text-input id="sub_name" name="name" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <x-input-label for="sub_email" value="Email" />
                            <x-text-input id="sub_email" type="email" name="email" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <x-input-label for="sub_password" value="Password" />
                            <x-text-input id="sub_password" type="password" name="password" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <x-input-label for="sub_team_id" value="Pilih Tim" />
                            <select id="sub_team_id" name="team_id" class="mt-1 block w-full" required>
                                <option value="">-- Pilih Tim --</option>
                                @foreach ($teams as $team)
                                    <option value="{{ $team->id }}">{{ $team->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <x-primary-button>Simpan Asisten Marketing</x-primary-button>
                    </form>
                </div>
            </div>

            <div class="panel fade-in-up">
                <h3 class="section-title">Tambah Tim</h3>
                <form class="mt-4 space-y-4" method="POST" action="{{ route('superadmin.teams.store') }}">
                    @csrf
                    <div>
                        <x-input-label for="team_name" value="Nama Tim" />
                        <x-text-input id="team_name" name="name" class="mt-1 block w-full" required />
                    </div>
                    <x-primary-button>Simpan Tim</x-primary-button>
                </form>
            </div>

            <div class="panel fade-in-up">
                <h3 class="section-title">Daftar Marketing Utama</h3>
                <div class="table-wrap mt-4">
                    <table class="table-clean">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Tim</th>
                                <th>Jumlah Asisten (tim sama)</th>
                                <th>Ubah Tim</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($leaders as $leader)
                                <tr>
                                    <td>{{ $leader->name }}</td>
                                    <td>{{ $leader->email }}</td>
                                    <td>{{ $leader->team?->name ?? '-' }}</td>
                                    <td>{{ $leader->sub_leaders_count }}</td>
                                    <td>
                                        <form class="flex gap-2" method="POST" action="{{ route('superadmin.users.assign-team', $leader) }}">
                                            @csrf
                                            @method('PATCH')
                                            <select name="team_id" class="text-sm">
                                                <option value="">-- Tidak ada --</option>
                                                @foreach ($teams as $team)
                                                    <option value="{{ $team->id }}" @selected($leader->team_id === $team->id)>{{ $team->name }}</option>
                                                @endforeach
                                            </select>
                                            <button class="btn-subtle px-3 py-2 text-xs">Update</button>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="POST" action="{{ route('superadmin.users.destroy', $leader) }}" onsubmit="return confirm('Hapus leader ini? Semua relasi akan dilepas.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-subtle px-3 py-2 text-xs text-red-600">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-4 text-slate-500">Belum ada leader.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if(!empty($trashedLeaders) && $trashedLeaders->isNotEmpty())
                <div class="panel fade-in-up">
                    <h3 class="section-title">Terhapus - Marketing Utama</h3>
                    <div class="table-wrap mt-4">
                        <table class="table-clean">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Tim</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($trashedLeaders as $t)
                                    <tr>
                                        <td>{{ $t->name }}</td>
                                        <td>{{ $t->email }}</td>
                                        <td>{{ $t->team?->name ?? '-' }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('superadmin.users.restore', $t->id) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn-subtle px-3 py-2 text-xs">Restore</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="panel fade-in-up">
                <h3 class="section-title">Daftar Assistant Marketing</h3>
                <div class="table-wrap mt-4">
                    <table class="table-clean">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Tim</th>
                                <th>Ubah Tim</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($subLeaders as $subLeader)
                                <tr>
                                    <td>{{ $subLeader->name }}</td>
                                    <td>{{ $subLeader->email }}</td>
                                    <td>{{ $subLeader->team?->name ?? '-' }}</td>
                                    <td>
                                        <form class="flex gap-2" method="POST" action="{{ route('superadmin.users.assign-team', $subLeader) }}">
                                            @csrf
                                            @method('PATCH')
                                            <select name="team_id" class="text-sm" required>
                                                <option value="">-- Pilih Tim --</option>
                                                @foreach ($teams as $team)
                                                    <option value="{{ $team->id }}" @selected($subLeader->team_id === $team->id)>
                                                        {{ $team->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button class="btn-subtle px-3 py-2 text-xs">Update</button>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="POST" action="{{ route('superadmin.users.destroy', $subLeader) }}" onsubmit="return confirm('Hapus asisten marketing ini? Semua relasi akan dilepas.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-subtle px-3 py-2 text-xs text-red-600">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-4 text-slate-500">Belum ada sub leader.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if(!empty($trashedSubLeaders) && $trashedSubLeaders->isNotEmpty())
                <div class="panel fade-in-up">
                    <h3 class="section-title">Terhapus - Assistant Marketing</h3>
                    <div class="table-wrap mt-4">
                        <table class="table-clean">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Tim</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($trashedSubLeaders as $t)
                                    <tr>
                                        <td>{{ $t->name }}</td>
                                        <td>{{ $t->email }}</td>
                                        <td>{{ $t->team?->name ?? '-' }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('superadmin.users.restore', $t->id) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn-subtle px-3 py-2 text-xs">Restore</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="panel fade-in-up">
                <h3 class="section-title">Daftar Tim</h3>
                <div class="table-wrap mt-4">
                    <table class="table-clean">
                        <thead>
                            <tr>
                                <th>Nama Tim</th>
                                <th>Jumlah Anggota</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($teams as $team)
                                <tr>
                                    <td>{{ $team->name }}</td>
                                    <td>{{ $team->members_count }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-4 py-4 text-slate-500">Belum ada tim.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
