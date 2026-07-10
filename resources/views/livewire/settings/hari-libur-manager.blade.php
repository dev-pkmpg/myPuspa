<div>
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-lg font-semibold text-gray-800">Daftar Hari Libur</h2>
        <button wire:click="$set('showForm', true)"
                class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
            + Tambah Hari Libur
        </button>
    </div>

    @if($showForm)
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">
            {{ $editingId ? 'Edit Hari Libur' : 'Tambah Hari Libur Baru' }}
        </h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal</label>
                <input wire:model="tanggal" type="date"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('tanggal') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Nama Hari Libur</label>
                <input wire:model="nama" type="text" placeholder="Contoh: Hari Raya Idul Fitri"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('nama') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Keterangan</label>
                <input wire:model="keterangan" type="text" placeholder="Opsional"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('keterangan') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="flex gap-3 mt-4">
            <button @click="Swal.fire({
                        title: '{{ $editingId ? 'Perbarui' : 'Simpan' }} Hari Libur?',
                        text: 'Pastikan tanggal dan nama hari libur sudah benar.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#4f46e5',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'Ya, {{ $editingId ? 'Perbarui' : 'Simpan' }}',
                        cancelButtonText: 'Batal',
                    }).then((result) => { if (result.isConfirmed) $wire.save() })"
                    class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
                {{ $editingId ? 'Perbarui' : 'Simpan' }}
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
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Nama Hari Libur</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Keterangan</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($hariLiburs as $libur)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">
                        {{ $libur->tanggal->translatedFormat('d F Y') }}
                    </td>
                    <td class="px-4 py-3 text-gray-800">{{ $libur->nama }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $libur->keterangan ?? '—' }}</td>
                    <td class="px-4 py-3 text-right space-x-3">
                        <button wire:click="edit({{ $libur->id }})"
                                class="text-indigo-400 hover:text-indigo-600 text-xs">Edit</button>
                        <button @click="Swal.fire({
                                    title: 'Hapus Hari Libur?',
                                    text: '&quot;{{ $libur->nama }}&quot; akan dihapus permanen.',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#ef4444',
                                    cancelButtonColor: '#6b7280',
                                    confirmButtonText: 'Ya, Hapus',
                                    cancelButtonText: 'Batal',
                                }).then((result) => { if (result.isConfirmed) $wire.delete({{ $libur->id }}) })"
                                class="text-red-400 hover:text-red-600 text-xs">Hapus</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-gray-400 text-sm">Belum ada hari libur.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
