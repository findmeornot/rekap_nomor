<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-semibold leading-tight text-slate-900">
                    Manajemen User
                </h2>
                <p class="mt-1 text-sm text-slate-600">Kelola role leader dan sub leader beserta relasinya.</p>
            </div>
            <a href="{{ route('superadmin.contacts.index') }}" class="btn-main">
                Lihat Data Per Leader
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
                    <h3 class="section-title">Tambah Leader</h3>
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
                        <x-primary-button>Simpan Leader</x-primary-button>
                    </form>
                </div>

                <div class="panel fade-in-up">
                    <h3 class="section-title">Tambah Sub Leader</h3>
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
                            <x-input-label for="leader_id" value="Pilih Leader" />
                            <select id="leader_id" name="leader_id" class="mt-1 block w-full" required>
                                <option value="">-- Pilih --</option>
                                @foreach ($leaders as $leader)
                                    <option value="{{ $leader->id }}">{{ $leader->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <x-primary-button>Simpan Sub Leader</x-primary-button>
                    </form>
                </div>
            </div>

            <div class="panel fade-in-up">
                <h3 class="section-title">Daftar Leader</h3>
                <div class="table-wrap mt-4">
                    <table class="table-clean">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Jumlah Sub Leader</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($leaders as $leader)
                                <tr>
                                    <td>{{ $leader->name }}</td>
                                    <td>{{ $leader->email }}</td>
                                    <td>{{ $leader->sub_leaders_count }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-4 text-slate-500">Belum ada leader.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel fade-in-up">
                <h3 class="section-title">Daftar Sub Leader</h3>
                <div class="table-wrap mt-4">
                    <table class="table-clean">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Leader Saat Ini</th>
                                <th>Ubah Leader</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($subLeaders as $subLeader)
                                <tr>
                                    <td>{{ $subLeader->name }}</td>
                                    <td>{{ $subLeader->email }}</td>
                                    <td>{{ $subLeader->leader?->name ?? '-' }}</td>
                                    <td>
                                        <form class="flex gap-2" method="POST" action="{{ route('superadmin.sub-leaders.assign-leader', $subLeader) }}">
                                            @csrf
                                            @method('PATCH')
                                            <select name="leader_id" class="text-sm" required>
                                                @foreach ($leaders as $leader)
                                                    <option value="{{ $leader->id }}" @selected($subLeader->leader_id === $leader->id)>
                                                        {{ $leader->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button class="btn-subtle px-3 py-2 text-xs">
                                                Update
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-4 text-slate-500">Belum ada sub leader.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
