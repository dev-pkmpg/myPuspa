<div>
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-lg font-semibold text-gray-800">Daftar Pegawai</h2>
        <button wire:click="$set('showForm', true)"
                class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
            + Tambah Pegawai
        </button>
    </div>

    @if($showForm)
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">
            {{ $editingId ? 'Edit Data Pegawai' : 'Tambah Pegawai Baru' }}
        </h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Nama Lengkap</label>
                <input wire:model="nama_lengkap" type="text"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('nama_lengkap') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">NIP</label>
                <input wire:model="nip" type="text"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('nip') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">NRK <span class="text-gray-400">(opsional)</span></label>
                <input wire:model="nrk" type="text" placeholder="Nomor Registrasi Kepegawaian"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('nrk') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                <input wire:model="email" type="email"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">
                    Password {{ $editingId ? '(kosongkan jika tidak diubah)' : '' }}
                </label>
                <input wire:model="password" type="password"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal Masuk</label>
                <input wire:model="tanggal_masuk" type="date"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('tanggal_masuk') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Jabatan</label>
                <select wire:model="jabatan_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">— Pilih Jabatan —</option>
                    @foreach($jabatans as $jabatan)
                        <option value="{{ $jabatan->id }}">{{ $jabatan->nama_jabatan }}</option>
                    @endforeach
                </select>
                @error('jabatan_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Status Pegawai</label>
                <select wire:model="status_pegawai_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">— Pilih Status —</option>
                    @foreach($statusPegawais as $status)
                        <option value="{{ $status->id }}">{{ $status->nama_status }}</option>
                    @endforeach
                </select>
                @error('status_pegawai_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Klaster</label>
                <select wire:model="klaster"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">— Pilih Klaster —</option>
                    @foreach($klasterOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('klaster') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-center">
                <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                    <input wire:model="status_aktif" type="checkbox" class="rounded">
                    Pegawai Aktif
                </label>
            </div>
        </div>
        <div class="flex gap-3 mt-4">
            <button @click="Swal.fire({
                        title: '{{ $editingId ? 'Perbarui' : 'Simpan' }} Data Pegawai?',
                        text: 'Pastikan semua data pegawai sudah benar.',
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
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">NIP</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">NRK</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Nama</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Jabatan</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Klaster</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Aktif</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($employees as $employee)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $employee->nip }}</td>
                    <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $employee->nrk ?? '—' }}</td>
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $employee->nama_lengkap }}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs">{{ $employee->jabatan?->nama_jabatan ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs">{{ $employee->statusPegawai?->nama_status ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs">
                        {{ $employee->klaster ? ($klasterOptions[$employee->klaster] ?? $employee->klaster) : '—' }}
                    </td>
                    <td class="px-4 py-3">
                        <button wire:click="toggleStatusAktif({{ $employee->id }})"
                                class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $employee->status_aktif ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $employee->status_aktif ? 'Aktif' : 'Nonaktif' }}
                        </button>
                    </td>
                    <td class="px-4 py-3 text-right space-x-3">
                        <button wire:click="edit({{ $employee->id }})"
                                class="text-indigo-400 hover:text-indigo-600 text-xs">Edit</button>
                        <button @click="Swal.fire({
                                    title: 'Hapus Pegawai?',
                                    text: '&quot;{{ $employee->nama_lengkap }}&quot; akan dihapus permanen beserta akun login-nya.',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#ef4444',
                                    cancelButtonColor: '#6b7280',
                                    confirmButtonText: 'Ya, Hapus',
                                    cancelButtonText: 'Batal',
                                }).then((result) => { if (result.isConfirmed) $wire.delete({{ $employee->id }}) })"
                                class="text-red-400 hover:text-red-600 text-xs">Hapus</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-400 text-sm">Belum ada pegawai.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
