# Booking Ruangan Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Sistem booking ruangan dengan approval manager — pegawai bisa booking, manager approve/reject, admin kelola data ruangan.

**Architecture:** Mengikuti pola Livewire 3 yang sudah ada. Dua tabel baru (`ruangans`, `booking_ruangans`). Empat Livewire component. Role `manager` ditambahkan ke `User` model. Middleware `role` sudah support multi-role via `in_array`.

**Tech Stack:** Laravel 11, Livewire 3, Blade, Alpine.js, Tailwind CSS, SweetAlert2, PHPUnit

---

## File Map

**Baru:**
- `database/migrations/*_create_ruangans_table.php`
- `database/migrations/*_create_booking_ruangans_table.php`
- `app/Models/Ruangan.php`
- `app/Models/BookingRuangan.php`
- `app/Livewire/Ruangan/RuanganManager.php`
- `app/Livewire/Booking/BookingForm.php`
- `app/Livewire/Booking/BookingHistory.php`
- `app/Livewire/Booking/BookingApproval.php`
- `resources/views/livewire/ruangan/ruangan-manager.blade.php`
- `resources/views/livewire/booking/booking-form.blade.php`
- `resources/views/livewire/booking/booking-history.blade.php`
- `resources/views/livewire/booking/booking-approval.blade.php`
- `resources/views/pages/admin/ruangan.blade.php`
- `resources/views/pages/booking/form.blade.php`
- `resources/views/pages/booking/history.blade.php`
- `resources/views/pages/manager/booking.blade.php`
- `tests/Feature/Livewire/Ruangan/RuanganManagerTest.php`
- `tests/Feature/Livewire/Booking/BookingFormTest.php`
- `tests/Feature/Livewire/Booking/BookingHistoryTest.php`
- `tests/Feature/Livewire/Booking/BookingApprovalTest.php`

**Diubah:**
- `app/Models/User.php` — tambah `isManager()` dan `bookings()` relation
- `routes/web.php` — tambah routes booking, ruangan, manager
- `resources/views/components/sidebar.blade.php` — tambah section Ruangan & Manager

---

### Task 1: User model — tambah `isManager()` + `bookings()` relation

**Files:**
- Modify: `app/Models/User.php`

- [ ] **Step 1: Update User model**

`app/Models/User.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(BookingRuangan::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }
}
```

- [ ] **Step 2: Verifikasi tidak ada test yang rusak**

```bash
php artisan test
```

Expected: semua test yang ada tetap passed.

- [ ] **Step 3: Commit**

```bash
git add app/Models/User.php
git commit -m "feat: add isManager() and bookings() to User model"
```

---

### Task 2: Ruangan — migration + model

**Files:**
- Create: `database/migrations/*_create_ruangans_table.php`
- Create: `app/Models/Ruangan.php`

- [ ] **Step 1: Buat migration**

```bash
php artisan make:migration create_ruangans_table
```

Isi file migration yang dihasilkan:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ruangans', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->integer('kapasitas');
            $table->string('lokasi')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ruangans');
    }
};
```

- [ ] **Step 2: Buat model**

`app/Models/Ruangan.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ruangan extends Model
{
    protected $fillable = ['nama', 'kapasitas', 'lokasi', 'aktif'];

    protected $casts = ['aktif' => 'boolean'];

    public function bookings(): HasMany
    {
        return $this->hasMany(BookingRuangan::class);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }
}
```

- [ ] **Step 3: Jalankan migration**

```bash
php artisan migrate
```

Expected: `ruangans` table created successfully.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/*_create_ruangans_table.php app/Models/Ruangan.php
git commit -m "feat: add Ruangan model and migration"
```

---

### Task 3: BookingRuangan — migration + model

**Files:**
- Create: `database/migrations/*_create_booking_ruangans_table.php`
- Create: `app/Models/BookingRuangan.php`

- [ ] **Step 1: Buat migration**

```bash
php artisan make:migration create_booking_ruangans_table
```

Isi file migration yang dihasilkan:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_ruangans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ruangan_id')->constrained('ruangans')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->string('keperluan');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('catatan_manager')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_ruangans');
    }
};
```

- [ ] **Step 2: Buat model**

`app/Models/BookingRuangan.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingRuangan extends Model
{
    protected $fillable = [
        'ruangan_id', 'user_id', 'tanggal', 'jam_mulai',
        'jam_selesai', 'keperluan', 'status', 'catatan_manager',
    ];

    protected $casts = ['tanggal' => 'date:Y-m-d'];

    public function ruangan(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeBentrok(Builder $query, int $ruanganId, string $tanggal, string $jamMulai, string $jamSelesai, ?int $exceptId = null): Builder
    {
        return $query
            ->where('ruangan_id', $ruanganId)
            ->whereDate('tanggal', $tanggal)
            ->whereIn('status', ['pending', 'approved'])
            ->where('jam_mulai', '<', $jamSelesai)
            ->where('jam_selesai', '>', $jamMulai)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId));
    }
}
```

- [ ] **Step 3: Jalankan migration**

```bash
php artisan migrate
```

Expected: `booking_ruangans` table created successfully.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/*_create_booking_ruangans_table.php app/Models/BookingRuangan.php
git commit -m "feat: add BookingRuangan model and migration"
```

---

### Task 4: RuanganManager — Livewire component + view + test

**Files:**
- Create: `app/Livewire/Ruangan/RuanganManager.php`
- Create: `resources/views/livewire/ruangan/ruangan-manager.blade.php`
- Create: `tests/Feature/Livewire/Ruangan/RuanganManagerTest.php`

- [ ] **Step 1: Tulis test yang gagal**

`tests/Feature/Livewire/Ruangan/RuanganManagerTest.php`:

```php
<?php

namespace Tests\Feature\Livewire\Ruangan;

use App\Livewire\Ruangan\RuanganManager;
use App\Models\BookingRuangan;
use App\Models\Ruangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RuanganManagerTest extends TestCase
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
        Livewire::test(RuanganManager::class)->assertStatus(200);
    }

    public function test_can_add_ruangan(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(RuanganManager::class)
            ->set('nama', 'Ruang Rapat A')
            ->set('kapasitas', 10)
            ->set('lokasi', 'Lantai 2')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ruangans', ['nama' => 'Ruang Rapat A', 'kapasitas' => 10]);
    }

    public function test_save_validates_required_fields(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(RuanganManager::class)
            ->set('nama', '')
            ->set('kapasitas', '')
            ->call('save')
            ->assertHasErrors(['nama' => 'required', 'kapasitas' => 'required']);
    }

    public function test_can_edit_ruangan(): void
    {
        $ruangan = Ruangan::create(['nama' => 'Nama Lama', 'kapasitas' => 5, 'aktif' => true]);
        $this->actingAs($this->admin);

        Livewire::test(RuanganManager::class)
            ->call('edit', $ruangan->id)
            ->set('nama', 'Nama Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ruangans', ['id' => $ruangan->id, 'nama' => 'Nama Baru']);
    }

    public function test_can_toggle_aktif(): void
    {
        $ruangan = Ruangan::create(['nama' => 'Ruang A', 'kapasitas' => 5, 'aktif' => true]);
        $this->actingAs($this->admin);

        Livewire::test(RuanganManager::class)->call('toggleAktif', $ruangan->id);

        $this->assertFalse($ruangan->fresh()->aktif);
    }

    public function test_cannot_delete_ruangan_with_active_booking(): void
    {
        $ruangan = Ruangan::create(['nama' => 'Ruang A', 'kapasitas' => 5, 'aktif' => true]);
        $user = User::factory()->create(['role' => 'pegawai']);
        BookingRuangan::create([
            'ruangan_id'  => $ruangan->id,
            'user_id'     => $user->id,
            'tanggal'     => today()->toDateString(),
            'jam_mulai'   => '09:00:00',
            'jam_selesai' => '10:00:00',
            'keperluan'   => 'Rapat',
            'status'      => 'pending',
        ]);
        $this->actingAs($this->admin);

        Livewire::test(RuanganManager::class)->call('delete', $ruangan->id);

        $this->assertDatabaseHas('ruangans', ['id' => $ruangan->id]);
    }

    public function test_can_delete_ruangan_without_active_booking(): void
    {
        $ruangan = Ruangan::create(['nama' => 'Ruang A', 'kapasitas' => 5, 'aktif' => true]);
        $this->actingAs($this->admin);

        Livewire::test(RuanganManager::class)->call('delete', $ruangan->id);

        $this->assertDatabaseMissing('ruangans', ['id' => $ruangan->id]);
    }
}
```

- [ ] **Step 2: Jalankan test — verifikasi gagal**

```bash
php artisan test tests/Feature/Livewire/Ruangan/RuanganManagerTest.php
```

Expected: FAIL — class RuanganManager not found.

- [ ] **Step 3: Buat Livewire component**

`app/Livewire/Ruangan/RuanganManager.php`:

```php
<?php

namespace App\Livewire\Ruangan;

use App\Models\Ruangan;
use Livewire\Component;

class RuanganManager extends Component
{
    public string $nama = '';
    public string $kapasitas = '';
    public string $lokasi = '';
    public bool $aktif = true;
    public bool $showForm = false;
    public ?int $editingId = null;

    protected $rules = [
        'nama'      => 'required|string|max:255',
        'kapasitas' => 'required|integer|min:1',
        'lokasi'    => 'nullable|string|max:255',
        'aktif'     => 'boolean',
    ];

    public function save(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $this->validate();

        $payload = [
            'nama'      => $this->nama,
            'kapasitas' => (int) $this->kapasitas,
            'lokasi'    => $this->lokasi ?: null,
            'aktif'     => $this->aktif,
        ];

        if ($this->editingId) {
            Ruangan::findOrFail($this->editingId)->update($payload);
            session()->flash('success', 'Ruangan berhasil diperbarui.');
        } else {
            Ruangan::create($payload);
            session()->flash('success', 'Ruangan berhasil ditambahkan.');
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $ruangan = Ruangan::findOrFail($id);
        $this->editingId = $id;
        $this->nama      = $ruangan->nama;
        $this->kapasitas = (string) $ruangan->kapasitas;
        $this->lokasi    = $ruangan->lokasi ?? '';
        $this->aktif     = $ruangan->aktif;
        $this->showForm  = true;
    }

    public function toggleAktif(int $id): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $ruangan = Ruangan::findOrFail($id);
        $ruangan->update(['aktif' => ! $ruangan->aktif]);
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $ruangan = Ruangan::findOrFail($id);

        if ($ruangan->bookings()->whereIn('status', ['pending', 'approved'])->exists()) {
            session()->flash('error', 'Ruangan tidak dapat dihapus karena masih ada booking aktif.');
            return;
        }

        $ruangan->delete();
        session()->flash('success', 'Ruangan berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset(['nama', 'kapasitas', 'lokasi', 'showForm', 'editingId']);
        $this->aktif = true;
    }

    public function render()
    {
        return view('livewire.ruangan.ruangan-manager', [
            'ruangans' => Ruangan::orderBy('nama')->get(),
        ]);
    }
}
```

- [ ] **Step 4: Buat view**

`resources/views/livewire/ruangan/ruangan-manager.blade.php`:

```html
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
        <h2 class="text-lg font-semibold text-gray-800">Daftar Ruangan</h2>
        <button wire:click="$set('showForm', true)"
                class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
            + Tambah Ruangan
        </button>
    </div>

    @if($showForm)
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">
            {{ $editingId ? 'Edit Ruangan' : 'Tambah Ruangan Baru' }}
        </h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Nama Ruangan</label>
                <input wire:model="nama" type="text" placeholder="Contoh: Ruang Rapat A"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('nama') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Kapasitas (orang)</label>
                <input wire:model="kapasitas" type="number" min="1" placeholder="10"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('kapasitas') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Lokasi</label>
                <input wire:model="lokasi" type="text" placeholder="Contoh: Lantai 2, Gedung A"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('lokasi') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
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
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Nama</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Kapasitas</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Lokasi</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($ruangans as $ruangan)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $ruangan->nama }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $ruangan->kapasitas }} orang</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $ruangan->lokasi ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <button wire:click="toggleAktif({{ $ruangan->id }})"
                                class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $ruangan->aktif ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $ruangan->aktif ? 'Aktif' : 'Nonaktif' }}
                        </button>
                    </td>
                    <td class="px-4 py-3 text-right space-x-3">
                        <button wire:click="edit({{ $ruangan->id }})"
                                class="text-indigo-400 hover:text-indigo-600 text-xs">Edit</button>
                        <button @click="Swal.fire({
                                    title: 'Hapus Ruangan?',
                                    text: '&quot;{{ $ruangan->nama }}&quot; akan dihapus permanen.',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#ef4444',
                                    cancelButtonColor: '#6b7280',
                                    confirmButtonText: 'Ya, Hapus',
                                    cancelButtonText: 'Batal',
                                }).then((result) => { if (result.isConfirmed) $wire.delete({{ $ruangan->id }}) })"
                                class="text-red-400 hover:text-red-600 text-xs">Hapus</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-400 text-sm">Belum ada ruangan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
```

- [ ] **Step 5: Jalankan test**

```bash
php artisan test tests/Feature/Livewire/Ruangan/RuanganManagerTest.php
```

Expected: 7 tests, 7 passed.

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/Ruangan/RuanganManager.php \
        resources/views/livewire/ruangan/ruangan-manager.blade.php \
        tests/Feature/Livewire/Ruangan/RuanganManagerTest.php
git commit -m "feat: add RuanganManager Livewire component"
```

---

### Task 5: BookingForm — Livewire component + view + test

**Files:**
- Create: `app/Livewire/Booking/BookingForm.php`
- Create: `resources/views/livewire/booking/booking-form.blade.php`
- Create: `tests/Feature/Livewire/Booking/BookingFormTest.php`

- [ ] **Step 1: Tulis test yang gagal**

`tests/Feature/Livewire/Booking/BookingFormTest.php`:

```php
<?php

namespace Tests\Feature\Livewire\Booking;

use App\Livewire\Booking\BookingForm;
use App\Models\BookingRuangan;
use App\Models\Ruangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BookingFormTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Ruangan $ruangan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user    = User::factory()->create(['role' => 'pegawai']);
        $this->ruangan = Ruangan::create(['nama' => 'Ruang Rapat A', 'kapasitas' => 10, 'aktif' => true]);
    }

    public function test_component_renders(): void
    {
        $this->actingAs($this->user);
        Livewire::test(BookingForm::class)->assertStatus(200);
    }

    public function test_can_submit_booking(): void
    {
        $this->actingAs($this->user);

        Livewire::test(BookingForm::class)
            ->set('ruangan_id', $this->ruangan->id)
            ->set('tanggal', today()->addDay()->toDateString())
            ->set('jam_mulai', '09:00')
            ->set('jam_selesai', '10:00')
            ->set('keperluan', 'Rapat Tim')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('booking_ruangans', [
            'ruangan_id' => $this->ruangan->id,
            'user_id'    => $this->user->id,
            'keperluan'  => 'Rapat Tim',
            'status'     => 'pending',
        ]);
    }

    public function test_save_validates_required_fields(): void
    {
        $this->actingAs($this->user);

        Livewire::test(BookingForm::class)
            ->set('ruangan_id', '')
            ->set('tanggal', '')
            ->set('jam_mulai', '')
            ->set('jam_selesai', '')
            ->set('keperluan', '')
            ->call('save')
            ->assertHasErrors(['ruangan_id', 'tanggal', 'jam_mulai', 'jam_selesai', 'keperluan']);
    }

    public function test_save_rejects_booking_with_time_conflict(): void
    {
        BookingRuangan::create([
            'ruangan_id'  => $this->ruangan->id,
            'user_id'     => $this->user->id,
            'tanggal'     => today()->addDay()->toDateString(),
            'jam_mulai'   => '09:00:00',
            'jam_selesai' => '11:00:00',
            'keperluan'   => 'Rapat Lama',
            'status'      => 'approved',
        ]);
        $this->actingAs($this->user);

        Livewire::test(BookingForm::class)
            ->set('ruangan_id', $this->ruangan->id)
            ->set('tanggal', today()->addDay()->toDateString())
            ->set('jam_mulai', '10:00')
            ->set('jam_selesai', '11:30')
            ->set('keperluan', 'Rapat Baru')
            ->call('save')
            ->assertHasErrors(['jam_mulai']);
    }

    public function test_save_allows_booking_when_no_conflict(): void
    {
        BookingRuangan::create([
            'ruangan_id'  => $this->ruangan->id,
            'user_id'     => $this->user->id,
            'tanggal'     => today()->addDay()->toDateString(),
            'jam_mulai'   => '09:00:00',
            'jam_selesai' => '10:00:00',
            'keperluan'   => 'Rapat Lama',
            'status'      => 'approved',
        ]);
        $this->actingAs($this->user);

        Livewire::test(BookingForm::class)
            ->set('ruangan_id', $this->ruangan->id)
            ->set('tanggal', today()->addDay()->toDateString())
            ->set('jam_mulai', '10:00')
            ->set('jam_selesai', '11:00')
            ->set('keperluan', 'Rapat Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('booking_ruangans', 2);
    }
}
```

- [ ] **Step 2: Jalankan test — verifikasi gagal**

```bash
php artisan test tests/Feature/Livewire/Booking/BookingFormTest.php
```

Expected: FAIL — class BookingForm not found.

- [ ] **Step 3: Buat Livewire component**

`app/Livewire/Booking/BookingForm.php`:

```php
<?php

namespace App\Livewire\Booking;

use App\Models\BookingRuangan;
use App\Models\Ruangan;
use Livewire\Component;

class BookingForm extends Component
{
    public string $ruangan_id = '';
    public string $tanggal = '';
    public string $jam_mulai = '';
    public string $jam_selesai = '';
    public string $keperluan = '';

    protected $rules = [
        'ruangan_id'  => 'required|exists:ruangans,id',
        'tanggal'     => 'required|date',
        'jam_mulai'   => 'required|date_format:H:i',
        'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
        'keperluan'   => 'required|string|max:255',
    ];

    public function save(): void
    {
        $this->validate();

        $ada_konflik = BookingRuangan::bentrok(
            (int) $this->ruangan_id,
            $this->tanggal,
            $this->jam_mulai . ':00',
            $this->jam_selesai . ':00'
        )->exists();

        if ($ada_konflik) {
            $this->addError('jam_mulai', 'Ruangan sudah dibooking pada jam tersebut. Pilih waktu lain.');
            return;
        }

        BookingRuangan::create([
            'ruangan_id'  => (int) $this->ruangan_id,
            'user_id'     => auth()->id(),
            'tanggal'     => $this->tanggal,
            'jam_mulai'   => $this->jam_mulai . ':00',
            'jam_selesai' => $this->jam_selesai . ':00',
            'keperluan'   => $this->keperluan,
            'status'      => 'pending',
        ]);

        session()->flash('success', 'Booking berhasil diajukan. Menunggu persetujuan manager.');
        $this->reset(['ruangan_id', 'tanggal', 'jam_mulai', 'jam_selesai', 'keperluan']);
    }

    public function render()
    {
        return view('livewire.booking.booking-form', [
            'ruangans' => Ruangan::aktif()->orderBy('nama')->get(),
        ]);
    }
}
```

- [ ] **Step 4: Buat view**

`resources/views/livewire/booking/booking-form.blade.php`:

```html
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
```

- [ ] **Step 5: Jalankan test**

```bash
php artisan test tests/Feature/Livewire/Booking/BookingFormTest.php
```

Expected: 4 tests, 4 passed.

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/Booking/BookingForm.php \
        resources/views/livewire/booking/booking-form.blade.php \
        tests/Feature/Livewire/Booking/BookingFormTest.php
git commit -m "feat: add BookingForm Livewire component with conflict check"
```

---

### Task 6: BookingHistory — Livewire component + view + test

**Files:**
- Create: `app/Livewire/Booking/BookingHistory.php`
- Create: `resources/views/livewire/booking/booking-history.blade.php`
- Create: `tests/Feature/Livewire/Booking/BookingHistoryTest.php`

- [ ] **Step 1: Tulis test yang gagal**

`tests/Feature/Livewire/Booking/BookingHistoryTest.php`:

```php
<?php

namespace Tests\Feature\Livewire\Booking;

use App\Livewire\Booking\BookingHistory;
use App\Models\BookingRuangan;
use App\Models\Ruangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BookingHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Ruangan $ruangan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user    = User::factory()->create(['role' => 'pegawai']);
        $this->ruangan = Ruangan::create(['nama' => 'Ruang A', 'kapasitas' => 10, 'aktif' => true]);
    }

    public function test_component_renders(): void
    {
        $this->actingAs($this->user);
        Livewire::test(BookingHistory::class)->assertStatus(200);
    }

    public function test_shows_only_own_bookings(): void
    {
        $other = User::factory()->create(['role' => 'pegawai']);
        BookingRuangan::create([
            'ruangan_id' => $this->ruangan->id, 'user_id' => $this->user->id,
            'tanggal' => today()->toDateString(), 'jam_mulai' => '09:00:00',
            'jam_selesai' => '10:00:00', 'keperluan' => 'Rapat Saya', 'status' => 'pending',
        ]);
        BookingRuangan::create([
            'ruangan_id' => $this->ruangan->id, 'user_id' => $other->id,
            'tanggal' => today()->toDateString(), 'jam_mulai' => '11:00:00',
            'jam_selesai' => '12:00:00', 'keperluan' => 'Rapat Orang Lain', 'status' => 'pending',
        ]);
        $this->actingAs($this->user);

        Livewire::test(BookingHistory::class)
            ->assertSee('Rapat Saya')
            ->assertDontSee('Rapat Orang Lain');
    }

    public function test_can_cancel_pending_booking(): void
    {
        $booking = BookingRuangan::create([
            'ruangan_id' => $this->ruangan->id, 'user_id' => $this->user->id,
            'tanggal' => today()->toDateString(), 'jam_mulai' => '09:00:00',
            'jam_selesai' => '10:00:00', 'keperluan' => 'Rapat', 'status' => 'pending',
        ]);
        $this->actingAs($this->user);

        Livewire::test(BookingHistory::class)->call('cancel', $booking->id);

        $this->assertDatabaseHas('booking_ruangans', ['id' => $booking->id, 'status' => 'rejected']);
    }

    public function test_cannot_cancel_approved_booking(): void
    {
        $booking = BookingRuangan::create([
            'ruangan_id' => $this->ruangan->id, 'user_id' => $this->user->id,
            'tanggal' => today()->toDateString(), 'jam_mulai' => '09:00:00',
            'jam_selesai' => '10:00:00', 'keperluan' => 'Rapat', 'status' => 'approved',
        ]);
        $this->actingAs($this->user);

        Livewire::test(BookingHistory::class)->call('cancel', $booking->id);

        $this->assertDatabaseHas('booking_ruangans', ['id' => $booking->id, 'status' => 'approved']);
    }
}
```

- [ ] **Step 2: Jalankan test — verifikasi gagal**

```bash
php artisan test tests/Feature/Livewire/Booking/BookingHistoryTest.php
```

Expected: FAIL — class BookingHistory not found.

- [ ] **Step 3: Buat Livewire component**

`app/Livewire/Booking/BookingHistory.php`:

```php
<?php

namespace App\Livewire\Booking;

use App\Models\BookingRuangan;
use Livewire\Component;

class BookingHistory extends Component
{
    public function cancel(int $id): void
    {
        $booking = BookingRuangan::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($booking->status !== 'pending') {
            session()->flash('error', 'Hanya booking dengan status pending yang dapat dibatalkan.');
            return;
        }

        $booking->update(['status' => 'rejected', 'catatan_manager' => 'Dibatalkan oleh pemohon.']);
        session()->flash('success', 'Booking berhasil dibatalkan.');
    }

    public function render()
    {
        return view('livewire.booking.booking-history', [
            'bookings' => BookingRuangan::with('ruangan')
                ->where('user_id', auth()->id())
                ->orderByDesc('tanggal')
                ->orderByDesc('jam_mulai')
                ->get(),
        ]);
    }
}
```

- [ ] **Step 4: Buat view**

`resources/views/livewire/booking/booking-history.blade.php`:

```html
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
        <h2 class="text-lg font-semibold text-gray-800">Riwayat Booking Saya</h2>
        <a href="{{ route('booking.form') }}"
           class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
            + Booking Baru
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Ruangan</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Jam</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Keperluan</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Catatan</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($bookings as $booking)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $booking->ruangan->nama }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $booking->tanggal->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs">
                        {{ substr($booking->jam_mulai, 0, 5) }} – {{ substr($booking->jam_selesai, 0, 5) }}
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $booking->keperluan }}</td>
                    <td class="px-4 py-3">
                        @php
                            $badge = match($booking->status) {
                                'pending'  => 'bg-yellow-100 text-yellow-700',
                                'approved' => 'bg-green-100 text-green-700',
                                'rejected' => 'bg-red-100 text-red-700',
                            };
                        @endphp
                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge }}">
                            {{ ucfirst($booking->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $booking->catatan_manager ?? '—' }}</td>
                    <td class="px-4 py-3 text-right">
                        @if($booking->status === 'pending')
                        <button @click="Swal.fire({
                                    title: 'Batalkan Booking?',
                                    text: 'Booking ini akan dibatalkan.',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#ef4444',
                                    cancelButtonColor: '#6b7280',
                                    confirmButtonText: 'Ya, Batalkan',
                                    cancelButtonText: 'Tidak',
                                }).then((result) => { if (result.isConfirmed) $wire.cancel({{ $booking->id }}) })"
                                class="text-red-400 hover:text-red-600 text-xs">Batalkan</button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">Belum ada booking.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
```

- [ ] **Step 5: Jalankan test**

```bash
php artisan test tests/Feature/Livewire/Booking/BookingHistoryTest.php
```

Expected: 4 tests, 4 passed.

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/Booking/BookingHistory.php \
        resources/views/livewire/booking/booking-history.blade.php \
        tests/Feature/Livewire/Booking/BookingHistoryTest.php
git commit -m "feat: add BookingHistory Livewire component"
```

---

### Task 7: BookingApproval — Livewire component + view + test

**Files:**
- Create: `app/Livewire/Booking/BookingApproval.php`
- Create: `resources/views/livewire/booking/booking-approval.blade.php`
- Create: `tests/Feature/Livewire/Booking/BookingApprovalTest.php`

- [ ] **Step 1: Tulis test yang gagal**

`tests/Feature/Livewire/Booking/BookingApprovalTest.php`:

```php
<?php

namespace Tests\Feature\Livewire\Booking;

use App\Livewire\Booking\BookingApproval;
use App\Models\BookingRuangan;
use App\Models\Ruangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BookingApprovalTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private BookingRuangan $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = User::factory()->create(['role' => 'manager']);
        $pegawai       = User::factory()->create(['role' => 'pegawai']);
        $ruangan       = Ruangan::create(['nama' => 'Ruang A', 'kapasitas' => 10, 'aktif' => true]);
        $this->booking = BookingRuangan::create([
            'ruangan_id'  => $ruangan->id,
            'user_id'     => $pegawai->id,
            'tanggal'     => today()->addDay()->toDateString(),
            'jam_mulai'   => '09:00:00',
            'jam_selesai' => '10:00:00',
            'keperluan'   => 'Rapat',
            'status'      => 'pending',
        ]);
    }

    public function test_component_renders_for_manager(): void
    {
        $this->actingAs($this->manager);
        Livewire::test(BookingApproval::class)->assertStatus(200);
    }

    public function test_can_approve_booking(): void
    {
        $this->actingAs($this->manager);

        Livewire::test(BookingApproval::class)->call('approve', $this->booking->id);

        $this->assertDatabaseHas('booking_ruangans', ['id' => $this->booking->id, 'status' => 'approved']);
    }

    public function test_can_reject_booking_with_catatan(): void
    {
        $this->actingAs($this->manager);

        Livewire::test(BookingApproval::class)
            ->call('reject', $this->booking->id, 'Ruangan sedang dalam perbaikan.');

        $this->assertDatabaseHas('booking_ruangans', [
            'id'              => $this->booking->id,
            'status'          => 'rejected',
            'catatan_manager' => 'Ruangan sedang dalam perbaikan.',
        ]);
    }

    public function test_non_manager_cannot_approve(): void
    {
        $pegawai = User::factory()->create(['role' => 'pegawai']);
        $this->actingAs($pegawai);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        Livewire::test(BookingApproval::class)->call('approve', $this->booking->id);
    }
}
```

- [ ] **Step 2: Jalankan test — verifikasi gagal**

```bash
php artisan test tests/Feature/Livewire/Booking/BookingApprovalTest.php
```

Expected: FAIL — class BookingApproval not found.

- [ ] **Step 3: Buat Livewire component**

`app/Livewire/Booking/BookingApproval.php`:

```php
<?php

namespace App\Livewire\Booking;

use App\Models\BookingRuangan;
use Livewire\Component;

class BookingApproval extends Component
{
    public function approve(int $id): void
    {
        abort_unless(auth()->user()?->isManager(), 403);
        BookingRuangan::findOrFail($id)->update(['status' => 'approved']);
        session()->flash('success', 'Booking disetujui.');
    }

    public function reject(int $id, string $catatan = ''): void
    {
        abort_unless(auth()->user()?->isManager(), 403);
        BookingRuangan::findOrFail($id)->update([
            'status'          => 'rejected',
            'catatan_manager' => $catatan ?: null,
        ]);
        session()->flash('success', 'Booking ditolak.');
    }

    public function render()
    {
        return view('livewire.booking.booking-approval', [
            'bookings' => BookingRuangan::with(['ruangan', 'user'])
                ->pending()
                ->orderBy('tanggal')
                ->orderBy('jam_mulai')
                ->get(),
        ]);
    }
}
```

- [ ] **Step 4: Buat view**

`resources/views/livewire/booking/booking-approval.blade.php`:

```html
<div>
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6">
        <h2 class="text-lg font-semibold text-gray-800">Approval Booking Ruangan</h2>
        <p class="text-sm text-gray-500 mt-1">Menampilkan semua booking yang menunggu persetujuan.</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Pemohon</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Ruangan</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Jam</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Keperluan</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($bookings as $booking)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-800">{{ $booking->user->name }}</td>
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $booking->ruangan->nama }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $booking->tanggal->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs">
                        {{ substr($booking->jam_mulai, 0, 5) }} – {{ substr($booking->jam_selesai, 0, 5) }}
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $booking->keperluan }}</td>
                    <td class="px-4 py-3 text-right space-x-2">
                        <button wire:click="approve({{ $booking->id }})"
                                class="px-3 py-1 bg-green-100 text-green-700 hover:bg-green-200 text-xs font-medium rounded-lg">
                            Setujui
                        </button>
                        <button @click="Swal.fire({
                                    title: 'Tolak Booking?',
                                    input: 'text',
                                    inputLabel: 'Alasan penolakan (opsional)',
                                    inputPlaceholder: 'Masukkan alasan...',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#ef4444',
                                    cancelButtonColor: '#6b7280',
                                    confirmButtonText: 'Tolak',
                                    cancelButtonText: 'Batal',
                                }).then((result) => { if (result.isConfirmed) $wire.reject({{ $booking->id }}, result.value ?? '') })"
                                class="px-3 py-1 bg-red-100 text-red-700 hover:bg-red-200 text-xs font-medium rounded-lg">
                            Tolak
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-400 text-sm">Tidak ada booking yang menunggu persetujuan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
```

- [ ] **Step 5: Jalankan test**

```bash
php artisan test tests/Feature/Livewire/Booking/BookingApprovalTest.php
```

Expected: 4 tests, 4 passed.

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/Booking/BookingApproval.php \
        resources/views/livewire/booking/booking-approval.blade.php \
        tests/Feature/Livewire/Booking/BookingApprovalTest.php
git commit -m "feat: add BookingApproval Livewire component"
```

---

### Task 8: Routes + page views + sidebar

**Files:**
- Modify: `routes/web.php`
- Create: `resources/views/pages/admin/ruangan.blade.php`
- Create: `resources/views/pages/booking/form.blade.php`
- Create: `resources/views/pages/booking/history.blade.php`
- Create: `resources/views/pages/manager/booking.blade.php`
- Modify: `resources/views/components/sidebar.blade.php`

- [ ] **Step 1: Update routes/web.php**

Tambahkan baris berikut ke dalam `routes/web.php`. Jangan timpa seluruh file — tambahkan dua route booking setelah `attendance.history`, tambahkan `ruangan` di dalam grup admin, dan tambahkan grup manager baru:

```php
// Setelah route attendance.history:
Route::get('/booking', fn () => view('pages.booking.form'))->name('booking.form');
Route::get('/booking/riwayat', fn () => view('pages.booking.history'))->name('booking.history');

// Di dalam grup admin, setelah route hari-libur:
Route::get('/ruangan', fn () => view('pages.admin.ruangan'))->name('ruangan.index');

// Grup baru di luar grup admin, sebelum require auth.php:
Route::prefix('manager')->name('manager.')->middleware('role:manager')->group(function () {
    Route::get('/booking', fn () => view('pages.manager.booking'))->name('booking.index');
});
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
    Route::get('/booking', fn () => view('pages.booking.form'))->name('booking.form');
    Route::get('/booking/riwayat', fn () => view('pages.booking.history'))->name('booking.history');

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/absensi', fn () => view('pages.attendance.admin'))->name('attendance.index');
        Route::get('/pengaturan-jam', fn () => view('pages.settings.shifts'))->name('settings.shifts');
        Route::get('/hari-libur', fn () => view('pages.settings.holidays'))->name('settings.holidays');
        Route::get('/ruangan', fn () => view('pages.admin.ruangan'))->name('ruangan.index');

        Route::prefix('kepegawaian')->name('kepegawaian.')->group(function () {
            Route::get('/', fn () => view('pages.kepegawaian.employees'))->name('employees');
            Route::get('/jabatan', fn () => view('pages.kepegawaian.jabatan'))->name('jabatan');
            Route::get('/status-pegawai', fn () => view('pages.kepegawaian.status-pegawai'))->name('status-pegawai');
        });
    });

    Route::prefix('manager')->name('manager.')->middleware('role:manager')->group(function () {
        Route::get('/booking', fn () => view('pages.manager.booking'))->name('booking.index');
    });
});

require __DIR__ . '/auth.php';
```

- [ ] **Step 2: Buat page views**

`resources/views/pages/admin/ruangan.blade.php`:
```html
<x-layouts.app title="Kelola Ruangan">
    <livewire:ruangan.ruangan-manager />
</x-layouts.app>
```

`resources/views/pages/booking/form.blade.php`:
```html
<x-layouts.app title="Booking Ruangan">
    <livewire:booking.booking-form />
</x-layouts.app>
```

`resources/views/pages/booking/history.blade.php`:
```html
<x-layouts.app title="Riwayat Booking">
    <livewire:booking.booking-history />
</x-layouts.app>
```

`resources/views/pages/manager/booking.blade.php`:
```html
<x-layouts.app title="Approval Booking">
    <livewire:booking.booking-approval />
</x-layouts.app>
```

- [ ] **Step 3: Update sidebar**

Ganti seluruh isi `resources/views/components/sidebar.blade.php` dengan:

```html
<aside class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 flex flex-col">
    <div class="flex items-center h-16 px-6 border-b border-gray-200">
        <span class="text-xl font-bold text-indigo-600">myPuspa</span>
        <span class="ml-2 text-xs text-gray-400">Kepegawaian</span>
    </div>

    <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-1">
        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Dashboard
        </a>

        <div class="pt-4">
            <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Absensi</p>
            @auth
                @if(auth()->user()->role === 'pegawai')
                <a href="{{ route('attendance.clock-in') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('attendance.clock-in') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Clock In / Out
                </a>
                @endif
            @endauth
            @auth
            <a href="{{ route('attendance.history') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('attendance.history') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Riwayat Absensi
            </a>
            @endauth
        </div>

        @auth
        <div class="pt-4">
            <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Ruangan</p>
            <a href="{{ route('booking.form') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('booking.form') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Booking Ruangan
            </a>
            <a href="{{ route('booking.history') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('booking.history') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Riwayat Booking
            </a>
        </div>
        @endauth

        @auth
            @if(auth()->user()->role === 'manager')
            <div class="pt-4">
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Manager</p>
                <a href="{{ route('manager.booking.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('manager.booking.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Approval Booking
                </a>
            </div>
            @endif
        @endauth

        @auth
            @if(auth()->user()->role === 'admin')
            <div class="pt-4">
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Admin</p>
                <a href="{{ route('admin.attendance.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.attendance.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Monitor Absensi
                </a>
                <a href="{{ route('admin.settings.shifts') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.settings.shifts') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Pengaturan Jam Kerja
                </a>
                @if(Route::has('admin.settings.holidays'))
                <a href="{{ route('admin.settings.holidays') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.settings.holidays') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Hari Libur
                </a>
                @endif
                <a href="{{ route('admin.ruangan.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.ruangan.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    Kelola Ruangan
                </a>

                <div x-data="{ open: {{ request()->routeIs('admin.kepegawaian.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open"
                            class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.kepegawaian.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        Kepegawaian
                        <svg class="ml-auto w-4 h-4 transition-transform duration-200"
                             :class="open ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" class="ml-4 mt-1 space-y-1">
                        <a href="{{ route('admin.kepegawaian.employees') }}"
                           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.kepegawaian.employees') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                            Data Pegawai
                        </a>
                        <a href="{{ route('admin.kepegawaian.jabatan') }}"
                           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.kepegawaian.jabatan') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                            Jabatan
                        </a>
                        <a href="{{ route('admin.kepegawaian.status-pegawai') }}"
                           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.kepegawaian.status-pegawai') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                            Status Pegawai
                        </a>
                    </div>
                </div>
            </div>
            @endif
        @endauth

        <div class="pt-4">
            <p class="px-3 text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">Segera Hadir</p>
            @foreach(['Penggajian', 'Penilaian Kinerja'] as $module)
            <span class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-300 cursor-not-allowed">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                {{ $module }}
                <span class="ml-auto text-xs bg-gray-100 text-gray-400 px-1.5 py-0.5 rounded">Soon</span>
            </span>
            @endforeach
        </div>
    </nav>

    @auth
    <div class="p-4 border-t border-gray-200">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-semibold text-sm">
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">{{ auth()->user()->name }}</p>
                <p class="text-xs text-gray-500 capitalize">{{ auth()->user()->role }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-gray-400 hover:text-gray-600" title="Logout">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
    @endauth
</aside>
```

- [ ] **Step 4: Jalankan seluruh test suite**

```bash
php artisan test
```

Expected: semua tests passed.

- [ ] **Step 5: Commit**

```bash
git add routes/web.php \
        resources/views/pages/admin/ruangan.blade.php \
        resources/views/pages/booking/form.blade.php \
        resources/views/pages/booking/history.blade.php \
        resources/views/pages/manager/booking.blade.php \
        resources/views/components/sidebar.blade.php
git commit -m "feat: add booking ruangan routes, page views, and sidebar"
```
