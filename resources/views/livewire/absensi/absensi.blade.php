<div>
    {{-- Banner error koneksi --}}
    @if(!$connected)
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-sm font-semibold text-red-700 mb-1">Koneksi ke database Absensi gagal</p>
        <p class="text-xs text-red-500 font-mono">{{ $connectionError }}</p>
        <p class="text-xs text-red-600 mt-2">Periksa konfigurasi <code>ATTENDANCE_DB_*</code> di file <code>.env</code>.</p>
    </div>
    @endif

    {{-- Filter --}}
    <div class="flex flex-wrap gap-3 mb-6 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Dari</label>
            <input wire:model.live="tanggal_dari" type="date"
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Sampai</label>
            <input wire:model.live="tanggal_sampai" type="date"
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        @if($isAdmin)
        <div class="flex-1 min-w-48">
            <label class="block text-xs font-medium text-gray-600 mb-1">Cari NRK</label>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Ketik NRK..."
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        @endif
    </div>

    @if($connected)
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    @if($isAdmin)
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">NRK</th>
                    @endif
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Nama Pegawai</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Tanggal & Jam</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Mesin</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($records as $row)
                <tr class="hover:bg-gray-50">
                    @if($isAdmin)
                    <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $row->pin }}</td>
                    @endif
                    <td class="px-4 py-3 font-medium text-gray-800">
                        {{ $nrkToName[$row->pin] ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-gray-600">
                        {{ \Carbon\Carbon::parse($row->date_time)->format('d/m/Y H:i:s') }}
                    </td>
                    <td class="px-4 py-3">
                        @if($row->status === 0)
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Masuk</span>
                        @else
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-700">Keluar</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $row->id_machine }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $isAdmin ? 5 : 4 }}" class="px-4 py-8 text-center text-gray-400 text-sm">
                        Tidak ada data untuk rentang tanggal ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($records->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $records->links() }}
        </div>
        @endif
    </div>
    @endif
</div>
