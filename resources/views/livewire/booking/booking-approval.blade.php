<div>
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-lg font-semibold text-gray-800">Persetujuan Booking Ruangan</h2>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Pemohon</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Ruangan</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Jam</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Keperluan</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($bookings as $booking)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $booking->user->name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $booking->ruangan->nama }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $booking->tanggal->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs">
                        {{ substr($booking->jam_mulai, 0, 5) }} – {{ substr($booking->jam_selesai, 0, 5) }}
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $booking->keperluan }}</td>
                    <td class="px-4 py-3 text-right flex gap-2 justify-end">
                        <button wire:click="approve({{ $booking->id }})"
                                class="px-3 py-1 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700">
                            Setujui
                        </button>
                        <button @click="Swal.fire({
                                    title: 'Tolak Booking?',
                                    input: 'text',
                                    inputLabel: 'Catatan (opsional)',
                                    inputPlaceholder: 'Alasan penolakan...',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#ef4444',
                                    cancelButtonColor: '#6b7280',
                                    confirmButtonText: 'Ya, Tolak',
                                    cancelButtonText: 'Batal',
                                }).then((result) => { if (result.isConfirmed) $wire.reject({{ $booking->id }}, result.value || '') })"
                                class="px-3 py-1 bg-red-100 text-red-600 text-xs font-medium rounded-lg hover:bg-red-200">
                            Tolak
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-400 text-sm">Tidak ada booking yang menunggu persetujuan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
