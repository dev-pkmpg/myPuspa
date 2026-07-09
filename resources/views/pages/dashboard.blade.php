<x-layouts.app title="Dashboard">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 max-w-4xl">
        @if(auth()->user()->role === 'pegawai')
            <livewire:attendance.clock-in-out />
        @else
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-2">Selamat Datang, Admin</h2>
                <p class="text-sm text-gray-500">Gunakan menu sidebar untuk mengelola absensi dan pengaturan sistem.</p>
            </div>
        @endif
    </div>
</x-layouts.app>
