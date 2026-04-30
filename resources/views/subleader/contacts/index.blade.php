<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold leading-tight text-slate-900">
                Input Nomor Sub Leader
            </h2>
            <p class="mt-1 text-sm text-slate-600">Input satuan atau massal, dengan validasi duplikat otomatis.</p>
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

            <div class="panel fade-in-up">
                <h3 class="section-title">Tambah Nomor</h3>
                <p class="section-subtitle">Input massal lewat textbox. Pisahkan nomor dengan spasi, enter, atau koma.</p>

                <form class="mt-4 grid gap-4" method="POST" action="{{ route('subleader.contacts.store') }}">
                    @csrf
                    <div>
                        <x-input-label for="contact_name" value="Nama Kontak (opsional)" />
                        <x-text-input id="contact_name" name="contact_name" class="mt-1 block w-full" :value="old('contact_name')" />
                    </div>
                    <div>
                        <x-input-label for="phones" value="Nomor (bisa banyak)" />
                        <textarea id="phones" name="phones" rows="5" required
                            class="mt-1 block w-full"
                            placeholder="Contoh: 08123456789 08129876543 628123111222">{{ old('phones', old('phone')) }}</textarea>
                        <p class="mt-2 text-xs text-gray-500">
                            Saat tekan spasi, sistem otomatis ubah jadi koma sebagai pemisah nomor berikutnya.
                        </p>
                    </div>
                    <div class="flex items-end">
                        <x-primary-button>Simpan Nomor</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="panel fade-in-up">
                <h3 class="section-title">Import Massal (Excel/CSV)</h3>
                <p class="section-subtitle">
                    Upload file dengan header kolom minimal <strong>phone</strong> atau <strong>nomor</strong>.
                    Kolom nama opsional: <strong>name</strong> / <strong>nama</strong> / <strong>contact_name</strong>.
                </p>
                <p class="mt-2 text-xs text-gray-500">
                    Format didukung: .csv, .xlsx, .xls (maks 5MB). Nomor duplikat otomatis dilewati.
                </p>

                <form class="mt-4 flex flex-wrap items-end gap-3" method="POST"
                    action="{{ route('subleader.contacts.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="w-full max-w-sm">
                        <x-input-label for="file" value="Pilih File" />
                        <input id="file" name="file" type="file" required accept=".csv,.txt,.xlsx,.xls"
                            class="mt-1 block w-full file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
                    </div>
                    <button type="submit" class="btn-main">Import File</button>
                </form>
            </div>

            <div class="panel fade-in-up">
                <h3 class="section-title">Nomor Yang Sudah Diinput</h3>
                <div class="table-wrap mt-4">
                    <table class="table-clean">
                        <thead>
                            <tr>
                                <th>Nama Kontak</th>
                                <th>Nomor</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($contacts as $contact)
                                <tr>
                                    <td>{{ $contact->contact_name ?? '-' }}</td>
                                    <td class="font-medium">{{ $contact->phone }}</td>
                                    <td>{{ $contact->created_at->format('d M Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-4 text-slate-500">Belum ada data nomor.</td>
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

    <script>
        (function () {
            const input = document.getElementById('phones');
            if (!input) {
                return;
            }

            input.addEventListener('keydown', function (event) {
                if (event.key !== ' ') {
                    return;
                }

                event.preventDefault();

                const start = input.selectionStart ?? input.value.length;
                const end = input.selectionEnd ?? input.value.length;
                const before = input.value.slice(0, start).replace(/(?:,\s*)?$/, ', ');
                const after = input.value.slice(end).replace(/^\s+/, '');

                input.value = before + after;
                input.selectionStart = input.selectionEnd = before.length;
            });
        })();
    </script>
</x-app-layout>
