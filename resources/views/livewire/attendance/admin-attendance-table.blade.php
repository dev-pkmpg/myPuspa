<div>
    <div class="flex flex-wrap gap-4 mb-6">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal</label>
            <input wire:model.live="filterTanggal" type="date"
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Status Kehadiran</label>
            <select wire:model.live="filterStatus"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Semua Status</option>
                <option value="hadir">Hadir</option>
                <option value="izin">Izin</option>
                <option value="sakit">Sakit</option>
                <option value="alfa">Alfa</option>
            </select>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Nama Pegawai</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">NIP</th>
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
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $record->employee->nama_lengkap }}</td>
                    <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ $record->employee->nip }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $record->tanggal->format('d/m/Y') }}</td>
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
                    <td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">Tidak ada data untuk filter yang dipilih.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($attendances->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">{{ $attendances->links() }}</div>
        @endif
    </div>
</div>
