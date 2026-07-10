<div class="bg-white rounded-xl border border-gray-200 p-6 max-w-md">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-semibold text-gray-800">Absensi Hari Ini</h2>
        <span class="text-sm text-gray-400"
              x-data="{ time: '' }"
              x-init="setInterval(() => { time = new Date().toLocaleTimeString('id-ID') }, 1000)"
              x-text="time"></span>
    </div>

    @if($hariLiburNama)
        <div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-lg flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <div>
                <p class="text-sm font-semibold text-amber-800">Hari Libur: {{ $hariLiburNama }}</p>
                <p class="text-xs text-amber-700 mt-0.5">Tidak perlu absen hari ini.</p>
            </div>
        </div>
    @else
        @if($errorMessage)
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
                {{ $errorMessage }}
            </div>
        @endif

        <div class="mb-6 p-4 bg-gray-50 rounded-lg grid grid-cols-2 gap-4">
            <div>
                <p class="text-xs text-gray-500 mb-1">Jam Masuk</p>
                <p class="text-sm font-semibold text-gray-800">
                    {{ $todayAttendance?->jam_masuk ? substr($todayAttendance->jam_masuk, 0, 5) : '—' }}
                </p>
                @if($todayAttendance?->keterangan)
                    <span class="text-xs text-orange-500">{{ $todayAttendance->keterangan }}</span>
                @endif
            </div>
            <div>
                <p class="text-xs text-gray-500 mb-1">Jam Pulang</p>
                <p class="text-sm font-semibold text-gray-800">
                    {{ $todayAttendance?->jam_pulang ? substr($todayAttendance->jam_pulang, 0, 5) : '—' }}
                </p>
            </div>
        </div>

        @if($todayAttendance?->jam_masuk && $todayAttendance?->jam_pulang)
            <div class="text-center py-4">
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-2">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <p class="text-sm text-gray-600">Absensi hari ini selesai. Sampai jumpa besok!</p>
            </div>
        @else
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 mb-1">Lokasi</label>
                <input wire:model="lokasi" type="text" placeholder="Contoh: Kantor Pusat, WFH"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('lokasi') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            @if(! $todayAttendance)
                <button wire:click="clockIn" wire:loading.attr="disabled"
                        class="w-full py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                    <span wire:loading.remove wire:target="clockIn">Absen Masuk</span>
                    <span wire:loading wire:target="clockIn">Memproses...</span>
                </button>
            @elseif(! $todayAttendance->jam_pulang)
                <button wire:click="clockOut" wire:loading.attr="disabled"
                        class="w-full py-2.5 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700 disabled:opacity-50">
                    <span wire:loading.remove wire:target="clockOut">Absen Pulang</span>
                    <span wire:loading wire:target="clockOut">Memproses...</span>
                </button>
            @endif
        @endif
    @endif
</div>
