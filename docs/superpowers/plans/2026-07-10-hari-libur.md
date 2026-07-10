# Hari Libur Module Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** CRUD hari libur berdasarkan tanggal spesifik, hanya admin, dengan blokir clock-in otomatis pada hari libur.

**Architecture:** Satu tabel `hari_liburs` dengan unique constraint pada kolom `tanggal`. Satu Livewire component `HariLiburManager` mengikuti pola ShiftManager. `ClockInOut` diupdate untuk cek hari libur saat mount. Sidebar mendapat link baru di section Admin.

**Tech Stack:** Laravel 11, Livewire 3, Blade, Alpine.js, Tailwind CSS, SweetAlert2 (sudah terpasang via npm), PHPUnit

**Prasyarat:** Plan ini independen — dapat dijalankan sebelum atau sesudah plan Kepegawaian.

---

## File Map

**Baru:**
- `database/migrations/*_create_hari_liburs_table.php`
- `app/Models/HariLibur.php`
- `app/Livewire/Settings/HariLiburManager.php`
- `resources/views/livewire/settings/hari-libur-manager.blade.php`
- `resources/views/pages/settings/holidays.blade.php`
- `tests/Feature/Livewire/HariLiburManagerTest.php`

**Diubah:**
- `app/Livewire/Attendance/ClockInOut.php` — cek hari libur di mount()
- `resources/views/livewire/attendance/clock-in-out.blade.php` — tampilkan pesan hari libur
- `routes/web.php` — tambah route hari libur
- `resources/views/components/sidebar.blade.php` — tambah link Hari Libur
- `tests/Feature/Livewire/ClockInOutTest.php` — tambah test holiday

---

### Task 1: HariLibur — migration + model

**Files:**
- Create: `database/migrations/*_create_hari_liburs_table.php`
- Create: `app/Models/HariLibur.php`

- [ ] **Step 1: Buat migration**

```bash
php artisan make:migration create_hari_liburs_table
```

Isi file yang dihasilkan:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hari_liburs', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            $table->string('nama');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hari_liburs');
    }
};
```

- [ ] **Step 2: Buat model HariLibur**

`app/Models/HariLibur.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HariLibur extends Model
{
    protected $fillable = ['tanggal', 'nama', 'keterangan'];

    protected $casts = ['tanggal' => 'date'];

    public function scopeOnDate(Builder $query, mixed $date): Builder
    {
        return $query->whereDate('tanggal', $date);
    }
}
```

- [ ] **Step 3: Jalankan migration**

```bash
php artisan migrate
```

Expected: `hari_liburs` table created successfully.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/*_create_hari_liburs_table.php app/Models/HariLibur.php
git commit -m "feat: add HariLibur model and migration"
```

---

### Task 2: HariLiburManager — Livewire component + view + test

**Files:**
- Create: `app/Livewire/Settings/HariLiburManager.php`
- Create: `resources/views/livewire/settings/hari-libur-manager.blade.php`
- Create: `tests/Feature/Livewire/HariLiburManagerTest.php`

- [ ] **Step 1: Tulis test yang gagal**

`tests/Feature/Livewire/HariLiburManagerTest.php`:

```php
<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Settings\HariLiburManager;
use App\Models\HariLibur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HariLiburManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_component_renders(): void
    {
        $this->actingAs($this->admin);
        Livewire::test(HariLiburManager::class)->assertStatus(200);
    }

    public function test_can_add_hari_libur(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(HariLiburManager::class)
            ->set('tanggal', '2026-08-17')
            ->set('nama', 'HUT RI ke-81')
            ->set('keterangan', 'Hari Kemerdekaan')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('hari_liburs', ['tanggal' => '2026-08-17', 'nama' => 'HUT RI ke-81']);
    }

    public function test_save_validates_required_fields(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(HariLiburManager::class)
            ->set('tanggal', '')
            ->set('nama', '')
            ->call('save')
            ->assertHasErrors(['tanggal' => 'required', 'nama' => 'required']);
    }

    public function test_cannot_add_duplicate_tanggal(): void
    {
        HariLibur::create(['tanggal' => '2026-08-17', 'nama' => 'HUT RI ke-81']);
        $this->actingAs($this->admin);

        Livewire::test(HariLiburManager::class)
            ->set('tanggal', '2026-08-17')
            ->set('nama', 'Nama Lain')
            ->call('save')
            ->assertHasErrors(['tanggal' => 'unique']);
    }

    public function test_can_edit_hari_libur(): void
    {
        $libur = HariLibur::create(['tanggal' => '2026-08-17', 'nama' => 'Nama Lama']);
        $this->actingAs($this->admin);

        Livewire::test(HariLiburManager::class)
            ->call('edit', $libur->id)
            ->set('nama', 'Nama Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('hari_liburs', ['id' => $libur->id, 'nama' => 'Nama Baru']);
    }

    public function test_can_delete_hari_libur(): void
    {
        $libur = HariLibur::create(['tanggal' => '2026-08-17', 'nama' => 'HUT RI ke-81']);
        $this->actingAs($this->admin);

        Livewire::test(HariLiburManager::class)->call('delete', $libur->id);

        $this->assertDatabaseMissing('hari_liburs', ['id' => $libur->id]);
    }
}
```

- [ ] **Step 2: Jalankan test — verifikasi gagal**

```bash
php artisan test tests/Feature/Livewire/HariLiburManagerTest.php
```

Expected: FAIL — class HariLiburManager not found.

- [ ] **Step 3: Buat Livewire component**

`app/Livewire/Settings/HariLiburManager.php`:

```php
<?php

namespace App\Livewire\Settings;

use App\Models\HariLibur;
use Livewire\Component;

class HariLiburManager extends Component
{
    public string $tanggal = '';
    public string $nama = '';
    public string $keterangan = '';
    public bool $showForm = false;
    public ?int $editingId = null;

    public function rules(): array
    {
        $tanggalRule = 'required|date|unique:hari_liburs,tanggal' . ($this->editingId ? ',' . $this->editingId : '');

        return [
            'tanggal'    => $tanggalRule,
            'nama'       => 'required|string|max:255',
            'keterangan' => 'nullable|string',
        ];
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $this->validate();

        $payload = [
            'tanggal'    => $this->tanggal,
            'nama'       => $this->nama,
            'keterangan' => $this->keterangan ?: null,
        ];

        if ($this->editingId) {
            HariLibur::findOrFail($this->editingId)->update($payload);
            session()->flash('success', 'Hari libur berhasil diperbarui.');
        } else {
            HariLibur::create($payload);
            session()->flash('success', 'Hari libur berhasil ditambahkan.');
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $libur = HariLibur::findOrFail($id);
        $this->editingId   = $id;
        $this->tanggal     = $libur->tanggal->format('Y-m-d');
        $this->nama        = $libur->nama;
        $this->keterangan  = $libur->keterangan ?? '';
        $this->showForm    = true;
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        HariLibur::findOrFail($id)->delete();
        session()->flash('success', 'Hari libur berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset(['tanggal', 'nama', 'keterangan', 'showForm', 'editingId']);
    }

    public function render()
    {
        return view('livewire.settings.hari-libur-manager', [
            'hariLiburs' => HariLibur::orderBy('tanggal')->get(),
        ]);
    }
}
```

- [ ] **Step 4: Buat view**

`resources/views/livewire/settings/hari-libur-manager.blade.php`:

```html
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
```

- [ ] **Step 5: Jalankan test**

```bash
php artisan test tests/Feature/Livewire/HariLiburManagerTest.php
```

Expected: 5 tests, 5 passed.

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/Settings/HariLiburManager.php \
        resources/views/livewire/settings/hari-libur-manager.blade.php \
        tests/Feature/Livewire/HariLiburManagerTest.php
git commit -m "feat: add HariLiburManager Livewire component"
```

---

### Task 3: Update ClockInOut — blokir clock-in saat hari libur

**Files:**
- Modify: `app/Livewire/Attendance/ClockInOut.php`
- Modify: `resources/views/livewire/attendance/clock-in-out.blade.php`
- Modify: `tests/Feature/Livewire/ClockInOutTest.php`

- [ ] **Step 1: Tulis test yang gagal**

Tambahkan dua method berikut ke class `ClockInOutTest` yang sudah ada di `tests/Feature/Livewire/ClockInOutTest.php`:

```php
use App\Models\HariLibur;

// Tambahkan setelah use statements yang sudah ada:

public function test_shows_holiday_message_when_today_is_holiday(): void
{
    HariLibur::create(['tanggal' => today()->toDateString(), 'nama' => 'Hari Raya Test']);
    $this->actingAs($this->user);

    Livewire::test(ClockInOut::class)
        ->assertSee('Hari Raya Test')
        ->assertDontSee('Absen Masuk');
}

public function test_allows_clock_in_when_not_a_holiday(): void
{
    $this->actingAs($this->user);

    Livewire::test(ClockInOut::class)->assertSee('Absen Masuk');
}
```

- [ ] **Step 2: Jalankan test baru — verifikasi gagal**

```bash
php artisan test tests/Feature/Livewire/ClockInOutTest.php --filter=test_shows_holiday_message
```

Expected: FAIL — "Hari Raya Test" tidak ada di view.

- [ ] **Step 3: Update ClockInOut component**

`app/Livewire/Attendance/ClockInOut.php`:

```php
<?php

namespace App\Livewire\Attendance;

use App\Models\Attendance;
use App\Models\HariLibur;
use App\Services\AttendanceService;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ClockInOut extends Component
{
    public string $lokasi = '';
    #[Locked]
    public ?Attendance $todayAttendance = null;
    public ?string $errorMessage = null;
    public ?string $hariLiburNama = null;

    protected $rules = ['lokasi' => 'required|string|max:255'];

    public function mount(AttendanceService $service): void
    {
        $employee = auth()->user()->employee;
        $this->todayAttendance = $employee ? $service->todayAttendance($employee) : null;

        $hariLibur = HariLibur::onDate(today())->first();
        $this->hariLiburNama = $hariLibur?->nama;
    }

    public function clockIn(AttendanceService $service): void
    {
        $this->validate();
        $this->errorMessage = null;

        $employee = auth()->user()->employee;
        if (! $employee) {
            $this->errorMessage = 'Data pegawai tidak ditemukan.';
            return;
        }

        try {
            $this->todayAttendance = $service->clockIn($employee, $this->lokasi);
            $this->lokasi = '';
        } catch (\RuntimeException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function clockOut(AttendanceService $service): void
    {
        $this->validate();
        $this->errorMessage = null;

        try {
            $this->todayAttendance = $service->clockOut($this->todayAttendance, $this->lokasi);
            $this->lokasi = '';
        } catch (\RuntimeException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.attendance.clock-in-out');
    }
}
```

- [ ] **Step 4: Update view clock-in-out**

`resources/views/livewire/attendance/clock-in-out.blade.php`:

```html
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
```

- [ ] **Step 5: Jalankan seluruh ClockInOut tests**

```bash
php artisan test tests/Feature/Livewire/ClockInOutTest.php
```

Expected: 6 tests, 6 passed.

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/Attendance/ClockInOut.php \
        resources/views/livewire/attendance/clock-in-out.blade.php \
        tests/Feature/Livewire/ClockInOutTest.php
git commit -m "feat: block clock-in on public holidays in ClockInOut component"
```

---

### Task 4: Route + page view + sidebar link

**Files:**
- Modify: `routes/web.php`
- Create: `resources/views/pages/settings/holidays.blade.php`
- Modify: `resources/views/components/sidebar.blade.php`

- [ ] **Step 1: Tambah route hari libur ke `routes/web.php`**

Tambahkan di dalam block `Route::prefix('admin')...`, setelah route `pengaturan-jam`:

```php
Route::get('/hari-libur', fn () => view('pages.settings.holidays'))->name('settings.holidays');
```

File `routes/web.php` lengkap setelah edit:

```php
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', fn () => view('pages.dashboard'))->name('dashboard');

    Route::middleware('role:pegawai')->group(function () {
        Route::get('/absensi/clock-in', fn () => view('pages.attendance.clock-in'))->name('attendance.clock-in');
    });

    Route::get('/absensi/riwayat', fn () => view('pages.attendance.history'))->name('attendance.history');

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/absensi', fn () => view('pages.attendance.admin'))->name('attendance.index');
        Route::get('/pengaturan-jam', fn () => view('pages.settings.shifts'))->name('settings.shifts');
        Route::get('/hari-libur', fn () => view('pages.settings.holidays'))->name('settings.holidays');
    });
});

require __DIR__ . '/auth.php';
```

> **Catatan:** Jika plan Kepegawaian sudah dijalankan duluan, `routes/web.php` sudah berisi route kepegawaian. Tambahkan hanya baris `hari-libur` di dalam group admin, jangan timpa seluruh file.

- [ ] **Step 2: Buat page view**

`resources/views/pages/settings/holidays.blade.php`:

```html
<x-layouts.app title="Hari Libur">
    <livewire:settings.hari-libur-manager />
</x-layouts.app>
```

- [ ] **Step 3: Tambah link Hari Libur ke sidebar**

> **Jika plan Kepegawaian sudah dijalankan:** sidebar sudah mengandung `@if(Route::has('admin.settings.holidays'))` — menambahkan route di Step 1 sudah cukup untuk memunculkan link. Lewati langkah edit sidebar ini dan langsung ke Step 4.

Di `resources/views/components/sidebar.blade.php`, tambahkan link berikut **setelah** link "Pengaturan Jam Kerja" dan **sebelum** penutup `</div>` section Admin:

```html
<a href="{{ route('admin.settings.holidays') }}"
   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.settings.holidays') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
    </svg>
    Hari Libur
</a>
```

- [ ] **Step 4: Jalankan seluruh test suite**

```bash
php artisan test
```

Expected: semua tests passed.

- [ ] **Step 5: Commit**

```bash
git add routes/web.php \
        resources/views/pages/settings/holidays.blade.php \
        resources/views/components/sidebar.blade.php
git commit -m "feat: add hari libur route, page view, and sidebar link"
```
