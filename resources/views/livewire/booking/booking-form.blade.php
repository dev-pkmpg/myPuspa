<div class="max-w-2xl">
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-6">Formulir Booking Ruangan</h2>

        <div class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Ruangan</label>
                <select wire:model="ruangan_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- Pilih Ruangan --</option>
                    @foreach($ruangans as $ruangan)
                        <option value="{{ $ruangan->id }}">
                            {{ $ruangan->nama }} ({{ $ruangan->kapasitas }} orang{{ $ruangan->lokasi ? ', ' . $ruangan->lokasi : '' }})
                        </option>
                    @endforeach
                </select>
                @error('ruangan_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal</label>
                <input wire:model="tanggal" type="date"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('tanggal') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Jam Mulai</label>
                    <input wire:model="jam_mulai" type="time"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('jam_mulai') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Jam Selesai</label>
                    <input wire:model="jam_selesai" type="time"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('jam_selesai') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Keperluan</label>
                <input wire:model="keperluan" type="text" placeholder="Contoh: Rapat Tim Divisi"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('keperluan') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="pt-2">
                <button wire:click="save" wire:loading.attr="disabled"
                        class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                    <span wire:loading.remove wire:target="save">Ajukan Booking</span>
                    <span wire:loading wire:target="save">Memproses...</span>
                </button>
            </div>
        </div>
    </div>
</div>
