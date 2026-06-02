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
                        <tbody data-colspan="6">
                            @forelse ($leaders as $leader)
                                <tr data-user-row="leader-{{ $leader->id }}">
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
                                    <td class="text-right">
                                        <button
                                            type="button"
                                            title="Hapus User"
                                            aria-label="Hapus User"
                                            class="btn-danger inline-flex h-10 w-10 items-center justify-center p-0"
                                            onclick="openDeleteUserModal(@js([
                                                'id' => $leader->id,
                                                'name' => $leader->name,
                                                'username' => $leader->email,
                                                'role' => 'Marketing Utama',
                                                'deleteUrl' => route('superadmin.users.destroy', $leader),
                                                'rowId' => 'leader-' . $leader->id,
                                            ]))"
                                        >
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M3 6h18"></path>
                                                <path d="M19 6l-1 14H6L5 6"></path>
                                                <path d="M10 11v6"></path>
                                                <path d="M14 11v6"></path>
                                                <path d="M9 6V4h6v2"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-4 text-slate-500">Belum ada leader.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

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
                        <tbody data-colspan="5">
                            @forelse ($subLeaders as $subLeader)
                                <tr data-user-row="subLeader-{{ $subLeader->id }}">
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
                                    <td class="text-right">
                                        <button
                                            type="button"
                                            title="Hapus User"
                                            aria-label="Hapus User"
                                            class="btn-danger inline-flex h-10 w-10 items-center justify-center p-0"
                                            onclick="openDeleteUserModal(@js([
                                                'id' => $subLeader->id,
                                                'name' => $subLeader->name,
                                                'username' => $subLeader->email,
                                                'role' => 'Asisten Marketing',
                                                'deleteUrl' => route('superadmin.users.destroy', $subLeader),
                                                'rowId' => 'subLeader-' . $subLeader->id,
                                            ]))"
                                        >
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M3 6h18"></path>
                                                <path d="M19 6l-1 14H6L5 6"></path>
                                                <path d="M10 11v6"></path>
                                                <path d="M14 11v6"></path>
                                                <path d="M9 6V4h6v2"></path>
                                            </svg>
                                        </button>
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

    <div id="toastContainer" class="fixed top-4 right-4 z-50 flex flex-col items-end gap-3"></div>

    <x-modal name="confirm-user-deletion" focusable maxWidth="md">
        <div class="p-6">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-100 text-red-600">
                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 8v4"></path>
                    <path d="M12 16h.01"></path>
                    <path d="M10 3h4"></path>
                    <path d="M7 6h10"></path>
                </svg>
            </div>
            <div class="mt-4 text-center">
                <h3 class="text-xl font-semibold text-slate-900">Hapus Data User</h3>
                <p class="mt-2 text-sm text-slate-600">Apakah Anda yakin ingin menghapus data user ini? Tindakan ini tidak dapat dibatalkan dan seluruh data terkait user akan dihapus secara permanen.</p>
            </div>
            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                <p class="font-semibold text-slate-900">Nama : <span id="deleteUserName"></span></p>
                <p class="mt-2">Username : <span id="deleteUserUsername"></span></p>
                <p class="mt-2">Role : <span id="deleteUserRole"></span></p>
            </div>
            <div id="deleteUserError" class="mt-4 hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"></div>
            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                <button type="button" class="btn-subtle w-full sm:w-auto" onclick="closeDeleteUserModal()">Batal</button>
                <button type="button" id="confirmDeleteButton" class="btn-danger w-full sm:w-auto inline-flex items-center justify-center gap-2" onclick="submitDeleteUser()" data-default-text="Hapus Permanen">
                    <span class="button-text">Hapus Permanen</span>
                </button>
            </div>
        </div>
    </x-modal>

    <script>
        const deleteUserState = {
            selectedUser: null,
            csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
        };

        function openDeleteUserModal(user) {
            deleteUserState.selectedUser = user;
            document.getElementById('deleteUserName').textContent = user.name ?? '-';
            document.getElementById('deleteUserUsername').textContent = user.username ?? '-';
            document.getElementById('deleteUserRole').textContent = user.role ?? '-';
            const errorElement = document.getElementById('deleteUserError');
            errorElement.classList.add('hidden');
            errorElement.textContent = '';
            const confirmButton = document.getElementById('confirmDeleteButton');
            confirmButton.disabled = false;
            confirmButton.innerHTML = '<span class="button-text">Hapus Permanen</span>';
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'confirm-user-deletion' }));
        }

        function closeDeleteUserModal() {
            window.dispatchEvent(new CustomEvent('close-modal', { detail: 'confirm-user-deletion' }));
        }

        async function submitDeleteUser() {
            const user = deleteUserState.selectedUser;
            if (!user) {
                return;
            }

            const button = document.getElementById('confirmDeleteButton');
            button.disabled = true;
            button.innerHTML = '<svg class="h-4 w-4 animate-spin text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg><span>Menghapus...</span>';

            try {
                const response = await fetch(user.deleteUrl, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': deleteUserState.csrfToken,
                        'Accept': 'application/json',
                    },
                });

                const payload = await response.json().catch(() => null);

                if (!response.ok || !payload?.ok) {
                    const errorMessage = payload?.message || 'Terjadi kesalahan saat menghapus data user.';
                    throw new Error(errorMessage);
                }

                closeDeleteUserModal();
                removeUserRow(user.rowId);
                showToast('Berhasil', 'Data user berhasil dihapus.', 'success');
            } catch (error) {
                const message = error instanceof Error ? error.message : 'Terjadi kesalahan saat menghapus data user.';
                const errorElement = document.getElementById('deleteUserError');
                errorElement.textContent = message;
                errorElement.classList.remove('hidden');
                showToast('Gagal', message, 'error');
            } finally {
                button.disabled = false;
                button.innerHTML = '<span class="button-text">Hapus Permanen</span>';
            }
        }

        function removeUserRow(rowId) {
            const row = document.querySelector(`[data-user-row="${rowId}"]`);
            if (!row) {
                return;
            }

            const tbody = row.closest('tbody');
            row.remove();

            if (tbody && tbody.querySelectorAll('tr').length === 0) {
                const placeholder = document.createElement('tr');
                placeholder.innerHTML = `<td colspan="${tbody.dataset.colspan}" class="px-4 py-4 text-slate-500">Belum ada user.</td>`;
                tbody.appendChild(placeholder);
            }
        }

        function showToast(title, message, type) {
            const container = document.getElementById('toastContainer');
            if (!container) {
                return;
            }

            const toast = document.createElement('div');
            const isSuccess = type === 'success';
            toast.className = 'max-w-sm rounded-2xl border px-4 py-3 shadow-xl transition duration-300';
            toast.classList.add(
                isSuccess ? 'border-emerald-200' : 'border-rose-200',
                isSuccess ? 'bg-emerald-50' : 'bg-rose-50',
                isSuccess ? 'text-emerald-700' : 'text-rose-700'
            );
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
            toast.style.transition = 'opacity 220ms ease, transform 220ms ease';
            toast.innerHTML = `
                <div class="flex items-start gap-3">
                    <div class="mt-0.5">
                        ${isSuccess ? '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 13l4 4L19 7"></path></svg>' : '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4"></path><path d="M12 17h.01"></path><path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'}
                    </div>
                    <div>
                        <p class="font-semibold">${title}</p>
                        <p class="mt-1 text-sm">${message}</p>
                    </div>
                </div>
            `;

            container.appendChild(toast);
            requestAnimationFrame(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateY(0)';
            });

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    toast.remove();
                }, 220);
            }, 4200);
        }
    </script>
</x-app-layout>
