# Absensi Wehopes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ganti halaman ZktecoAbsensi dengan halaman Absensi baru yang membaca dari database `wehopes` (tabel `tb_absen`) via koneksi `attendance`, dapat diakses semua pegawai — admin lihat semua, pegawai lihat data sendiri.

**Architecture:** Livewire component `Absensi` membaca dari `TbAbsen` model (koneksi `attendance`). Admin mendapat filter NRK; non-admin otomatis difilter ke NRK sendiri. Nama pegawai di-lookup dari DB utama via `users.nrk → employees.nama_lengkap`.

**Tech Stack:** Laravel 11, Livewire 3, Tailwind CSS, MySQL (2 koneksi: `mysql` dan `attendance`)

---

## File Map

| Aksi | Path |
|------|------|
| **Delete** | `app/Livewire/Absensi/ZktecoAbsensi.php` |
| **Delete** | `resources/views/livewire/absensi/zkteco-absensi.blade.php` |
| **Modify** | `resources/views/pages/attendance/zkteco.blade.php` → ganti isi |
| **Create** | `app/Livewire/Absensi/Absensi.php` |
| **Create** | `resources/views/livewire/absensi/absensi.blade.php` |
| **Modify** | `routes/web.php` — hapus route lama, tambah route baru |
| **Reference** | `app/Models/TbAbsen.php` — sudah ada, tidak perlu diubah |
| **Reference** | `app/Models/User.php` — method `isAdmin()` digunakan |

---

### Task 1: Hapus file ZktecoAbsensi dan buat Livewire component baru

**Files:**
- Delete: `app/Livewire/Absensi/ZktecoAbsensi.php`
- Delete: `resources/views/livewire/absensi/zkteco-absensi.blade.php`
- Create: `app/Livewire/Absensi/Absensi.php`

- [ ] **Step 1: Hapus file ZktecoAbsensi**

```bash
rm app/Livewire/Absensi/ZktecoAbsensi.php
rm resources/views/livewire/absensi/zkteco-absensi.blade.php
```

- [ ] **Step 2: Buat `app/Livewire/Absensi/Absensi.php`**

```php
<?php

namespace App\Livewire\Absensi;

use App\Models\TbAbsen;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Absensi extends Component
{
    use WithPagination;

    public string $tanggal_dari = '';
    public string $tanggal_sampai = '';
    public string $search = '';
    public bool $connected = false;
    public string $connectionError = '';

    public function mount(): void
    {
        $this->tanggal_dari   = now()->startOfMonth()->format('Y-m-d');
        $this->tanggal_sampai = now()->format('Y-m-d');
        $this->checkConnection();
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedTanggalDari(): void { $this->resetPage(); }
    public function updatedTanggalSampai(): void { $this->resetPage(); }

    private function checkConnection(): void
    {
        try {
            DB::connection('attendance')->getPdo();
            $this->connected = true;
        } catch (\Throwable $e) {
            $this->connected       = false;
            $this->connectionError = $e->getMessage();
        }
    }

    public function render()
    {
        $records   = collect();
        $nrkToName = collect();

        if ($this->connected) {
            $isAdmin = auth()->user()->isAdmin();

            $query = TbAbsen::query()
                ->whereBetween('date_time', [
                    $this->tanggal_dari . ' 00:00:00',
                    $this->tanggal_sampai . ' 23:59:59',
                ]);

            if ($isAdmin) {
                if ($this->search !== '') {
                    $query->where('pin', 'like', '%' . $this->search . '%');
                }
            } else {
                $query->where('pin', auth()->user()->nrk);
            }

            $query->orderByDesc('date_time');

            $pins      = (clone $query)->distinct()->pluck('pin');
            $nrkToName = User::whereIn('nrk', $pins)
                ->with('employee:user_id,nama_lengkap')
                ->get()
                ->mapWithKeys(fn ($u) => [$u->nrk => $u->employee?->nama_lengkap ?? $u->nrk]);

            $records = $query->paginate(50);
        }

        return view('livewire.absensi.absensi', [
            'records'   => $records,
            'nrkToName' => $nrkToName,
            'isAdmin'   => auth()->user()->isAdmin(),
        ]);
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Livewire/Absensi/Absensi.php
git rm app/Livewire/Absensi/ZktecoAbsensi.php
git rm resources/views/livewire/absensi/zkteco-absensi.blade.php
git commit -m "feat: add Absensi livewire component from wehopes DB, remove ZktecoAbsensi"
```

---

### Task 2: Buat blade template untuk komponen Absensi

**Files:**
- Create: `resources/views/livewire/absensi/absensi.blade.php`

- [ ] **Step 1: Buat `resources/views/livewire/absensi/absensi.blade.php`**

```blade
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
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/livewire/absensi/absensi.blade.php
git commit -m "feat: add absensi blade template with role-based NRK column"
```

---

### Task 3: Perbarui page wrapper dan routes

**Files:**
- Modify: `resources/views/pages/attendance/zkteco.blade.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Ganti isi `resources/views/pages/attendance/zkteco.blade.php`**

```blade
<x-layouts.app title="Data Absensi Mesin">
    <livewire:absensi.absensi />
</x-layouts.app>
```

- [ ] **Step 2: Perbarui `routes/web.php`**

Hapus route lama di dalam blok `admin`:
```php
Route::get('/absensi/mesin', fn () => view('pages.attendance.zkteco'))->name('attendance.zkteco');
```

Tambahkan route baru di blok `auth` umum (sebelum blok `manager`):
```php
Route::get('/absensi/mesin', fn () => view('pages.attendance.zkteco'))->name('attendance.mesin');
```

Hasil akhir blok routes yang relevan:
```php
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', fn () => view('pages.dashboard'))->name('dashboard');

    // Pegawai only
    Route::middleware('role:pegawai')->group(function () {
        Route::get('/absensi/clock-in', fn () => view('pages.attendance.clock-in'))->name('attendance.clock-in');
    });

    // Both roles
    Route::get('/absensi/riwayat', fn () => view('pages.attendance.history'))->name('attendance.history');
    Route::get('/absensi/mesin', fn () => view('pages.attendance.zkteco'))->name('attendance.mesin');

    // ... sisa routes tidak berubah

    // Admin only — hapus baris attendance.zkteco dari sini
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        // ...
        // HAPUS: Route::get('/absensi/mesin', ...)->name('attendance.zkteco');
    });
});
```

- [ ] **Step 3: Verifikasi route terdaftar dengan benar**

```bash
php artisan route:list --name=attendance
```

Expected output (antara lain):
```
GET  /absensi/mesin   attendance.mesin   ...
```

- [ ] **Step 4: Commit**

```bash
git add resources/views/pages/attendance/zkteco.blade.php routes/web.php
git commit -m "feat: move absensi/mesin route to all-auth access, point to new Absensi component"
```

---

### Task 4: Smoke test manual

- [ ] **Step 1: Jalankan development server**

```bash
php artisan serve
```

- [ ] **Step 2: Login sebagai admin, buka `/absensi/mesin`**

Verifikasi:
- Tabel tampil dengan kolom: NRK | Nama Pegawai | Tanggal & Jam | Status | Mesin
- Input "Cari NRK" muncul
- Badge "Masuk" (hijau) dan "Keluar" (oranye) tampil sesuai kolom `status`
- Filter tanggal bekerja
- Pagination muncul jika data > 50 baris

- [ ] **Step 3: Login sebagai pegawai biasa, buka `/absensi/mesin`**

Verifikasi:
- Kolom NRK **tidak tampil**
- Input "Cari NRK" **tidak tampil**
- Hanya data absensi milik NRK pegawai tersebut yang muncul

- [ ] **Step 4: Verifikasi error handling**

Ubah sementara `ATTENDANCE_DB_HOST` di `.env` menjadi alamat yang salah, refresh halaman.
Verifikasi: banner merah muncul dengan pesan error koneksi.
Kembalikan nilai `ATTENDANCE_DB_HOST` setelah selesai.
