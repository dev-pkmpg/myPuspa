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
        <h2 class="text-lg font-semibold text-gray-800">Daftar Status Pegawai</h2>
        <button wire:click="$set('showForm', true)"
                class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
            + Tambah Status
        </button>
    </div>

    @if($showForm)
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">
            {{ $editingId ? 'Edit Status Pegawai' : 'Tambah Status Pegawai Baru' }}
        </h3>
        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Nama Status</label>
                <input wire:model="nama_status" type="text" placeholder="Contoh: PNS, PPPK, Honorer"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('nama_status') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Keterangan</label>
                <textarea wire:model="keterangan" rows="2" placeholder="Opsional"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                @error('keterangan') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                    <input wire:model="aktif" type="checkbox" class="rounded">
                    Aktif
                </label>
            </div>
        </div>
        <div class="flex gap-3 mt-4">
            <button wire:click="save"
                    class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
                Simpan
            </button>
            <button wire:click="resetForm"
                    class="px-4 py-2 text-gray-600 text-sm rounded-lg hover:bg-gray-100">
                Batal
            </button>
        </div>
    </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Nama Status</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Keterangan</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($statusList as $status)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $status->nama_status }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $status->keterangan ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <button wire:click="toggleAktif({{ $status->id }})"
                                class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $status->aktif ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $status->aktif ? 'Aktif' : 'Nonaktif' }}
                        </button>
                    </td>
                    <td class="px-4 py-3 text-right space-x-3">
                        <button wire:click="edit({{ $status->id }})"
                                class="text-indigo-400 hover:text-indigo-600 text-xs">Edit</button>
                        <button @click="Swal.fire({
                                    title: 'Hapus Status?',
                                    text: 'Status &quot;{{ $status->nama_status }}&quot; akan dihapus permanen.',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#ef4444',
                                    cancelButtonColor: '#6b7280',
                                    confirmButtonText: 'Ya, Hapus',
                                    cancelButtonText: 'Batal',
                                }).then((result) => { if (result.isConfirmed) $wire.delete({{ $status->id }}) })"
                                class="text-red-400 hover:text-red-600 text-xs">Hapus</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-gray-400 text-sm">Belum ada status pegawai.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
