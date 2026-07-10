<div>
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-lg font-semibold text-gray-800">Riwayat Booking Saya</h2>
        @if(Route::has('booking.form'))
        <a href="{{ route('booking.form') }}"
           class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
            + Booking Baru
        </a>
        @endif
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Ruangan</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Jam</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Keperluan</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Catatan</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($bookings as $booking)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $booking->ruangan->nama }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $booking->tanggal->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs">
                        {{ substr($booking->jam_mulai, 0, 5) }} – {{ substr($booking->jam_selesai, 0, 5) }}
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $booking->keperluan }}</td>
                    <td class="px-4 py-3">
                        @php
                            $badge = match($booking->status) {
                                'pending'  => 'bg-yellow-100 text-yellow-700',
                                'approved' => 'bg-green-100 text-green-700',
                                'rejected' => 'bg-red-100 text-red-700',
                            };
                        @endphp
                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge }}">
                            {{ ucfirst($booking->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $booking->catatan_manager ?? '—' }}</td>
                    <td class="px-4 py-3 text-right">
                        @if($booking->status === 'pending')
                        <button @click="Swal.fire({
                                    title: 'Batalkan Booking?',
                                    text: 'Booking ini akan dibatalkan.',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#ef4444',
                                    cancelButtonColor: '#6b7280',
                                    confirmButtonText: 'Ya, Batalkan',
                                    cancelButtonText: 'Tidak',
                                }).then((result) => { if (result.isConfirmed) $wire.cancel({{ $booking->id }}) })"
                                class="text-red-400 hover:text-red-600 text-xs">Batalkan</button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">Belum ada booking.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
