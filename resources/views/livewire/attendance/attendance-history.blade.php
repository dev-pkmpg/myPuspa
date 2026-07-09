<div>
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Jam Masuk</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Jam Pulang</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Keterangan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($attendances as $record)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $record->tanggal->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $record->jam_masuk ? substr($record->jam_masuk, 0, 5) : '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $record->jam_pulang ? substr($record->jam_pulang, 0, 5) : '—' }}</td>
                    <td class="px-4 py-3">
                        @php $badge = match($record->status_kehadiran) {
                            'hadir' => 'bg-green-100 text-green-700',
                            'izin'  => 'bg-blue-100 text-blue-700',
                            'sakit' => 'bg-yellow-100 text-yellow-700',
                            'alfa'  => 'bg-red-100 text-red-700',
                            default => 'bg-gray-100 text-gray-600',
                        }; @endphp
                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge }} capitalize">
                            {{ $record->status_kehadiran }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $record->keterangan ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-400 text-sm">Belum ada riwayat absensi.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($attendances->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">{{ $attendances->links() }}</div>
        @endif
    </div>
</div>
