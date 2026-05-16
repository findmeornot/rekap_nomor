<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold leading-tight text-slate-900">
                Permintaan Nomor Antar Marketing Utama
            </h2>
            <p class="mt-1 text-sm text-slate-600">Kirim dan kelola request nomor antar tim Marketing Utama.</p>
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
                <h3 class="section-title">Buat Permintaan Baru</h3>
                <p class="section-subtitle">Minta jumlah nomor dari Marketing Utama lain bila timmu sudah bersih.</p>

                <form class="mt-4 grid gap-4" method="POST" action="{{ route('leader.requests.store') }}">
                    @csrf
                    <div>
                        <x-input-label for="recipient_id" value="Pilih Marketing Utama" />
                        <select id="recipient_id" name="recipient_id" class="mt-1 block w-full" required>
                            <option value="">-- Pilih --</option>
                            @foreach ($recipients as $recipient)
                                <option value="{{ $recipient->id }}" @selected(old('recipient_id') == $recipient->id)>
                                    {{ $recipient->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="amount" value="Jumlah Nomor" />
                        <x-text-input id="amount" name="amount" type="number" min="1" max="400" value="{{ old('amount', 1) }}" class="mt-1 block w-full" required />
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label for="message" value="Catatan / Alasan (opsional)" />
                        <textarea id="message" name="message" rows="3" class="mt-1 block w-full">{{ old('message') }}</textarea>
                    </div>
                    <div class="md:col-span-2 flex items-end">
                        <x-primary-button>Kirim Permintaan</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="panel fade-in-up">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="section-title">Riwayat Permintaan</h3>
                        <p class="section-subtitle">Lihat status, jumlah, dan tanggapan permintaan nomor antartim.</p>
                    </div>
                    <div class="text-sm text-slate-500">Total: {{ $requests->total() }}</div>
                </div>

                <div class="table-wrap mt-4">
                    <table class="table-clean">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Pengirim</th>
                                <th>Penerima</th>
                                <th>Jumlah</th>
                                <th>Status</th>
                                <th>Respon</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($requests as $requestItem)
                                <tr>
                                    <td>{{ $requestItem->created_at->format('d M Y H:i') }}</td>
                                    <td>{{ $requestItem->requester->name }}</td>
                                    <td>{{ $requestItem->recipient->name }}</td>
                                    <td>{{ $requestItem->amount }}</td>
                                    <td>
                                        <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold
                                            @if ($requestItem->status === 'pending') border-slate-300 bg-slate-100 text-slate-700
                                            @elseif ($requestItem->status === 'approved') border-emerald-200 bg-emerald-50 text-emerald-700
                                            @else border-rose-200 bg-rose-50 text-rose-700
                                            @endif">
                                            {{ ucfirst($requestItem->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $requestItem->response_message ?? '-' }}</td>
                                    <td>
                                        @if ($requestItem->recipient_id === auth()->id() && $requestItem->status === 'pending')
                                            <div class="flex flex-wrap gap-2">
                                                <form method="POST" action="{{ route('leader.requests.approve', $requestItem) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn-main px-3 py-2 text-xs">Setujui</button>
                                                </form>
                                                <form method="POST" action="{{ route('leader.requests.reject', $requestItem) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn-subtle px-3 py-2 text-xs">Tolak</button>
                                                </form>
                                            </div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-4 text-slate-500">Belum ada permintaan nomor.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $requests->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
