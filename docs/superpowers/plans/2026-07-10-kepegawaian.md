# Kepegawaian Module Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Aktifkan menu Kepegawaian dengan CRUD Data Pegawai, Jabatan, dan Status Pegawai — hanya dapat diakses admin.

**Architecture:** Tiga Livewire components mengikuti pola ShiftManager yang ada. Jabatan dan StatusPegawai adalah tabel master dengan kolom aktif/keterangan. EmployeeManager menggunakan EmployeeService untuk transaksi DB. Sidebar dikembangkan dengan submenu collapsible menggunakan Alpine.js.

**Tech Stack:** Laravel 11, Livewire 3, Blade, Alpine.js, Tailwind CSS, SweetAlert2 (sudah terpasang via npm), PHPUnit

---

## File Map

**Baru:**
- `database/migrations/*_create_jabatans_table.php`
- `database/migrations/*_create_status_pegawais_table.php`
- `database/migrations/*_add_fields_to_employees_table.php`
- `app/Models/Jabatan.php`
- `app/Models/StatusPegawai.php`
- `app/Livewire/Kepegawaian/JabatanManager.php`
- `app/Livewire/Kepegawaian/StatusPegawaiManager.php`
- `app/Livewire/Kepegawaian/EmployeeManager.php`
- `resources/views/livewire/kepegawaian/jabatan-manager.blade.php`
- `resources/views/livewire/kepegawaian/status-pegawai-manager.blade.php`
- `resources/views/livewire/kepegawaian/employee-manager.blade.php`
- `resources/views/pages/kepegawaian/jabatan.blade.php`
- `resources/views/pages/kepegawaian/status-pegawai.blade.php`
- `resources/views/pages/kepegawaian/employees.blade.php`
- `tests/Feature/Livewire/Kepegawaian/JabatanManagerTest.php`
- `tests/Feature/Livewire/Kepegawaian/StatusPegawaiManagerTest.php`
- `tests/Feature/Livewire/Kepegawaian/EmployeeManagerTest.php`

**Diubah:**
- `app/Models/Employee.php` — tambah fillable, casts, relasi jabatan & statusPegawai
- `app/Services/EmployeeService.php` — update create() + tambah update()
- `tests/Feature/Services/EmployeeServiceTest.php` — tambah test update()
- `routes/web.php` — tambah 3 route kepegawaian
- `resources/views/components/sidebar.blade.php` — aktifkan submenu Kepegawaian

---

### Task 1: Jabatan — migration + model

**Files:**
- Create: `database/migrations/*_create_jabatans_table.php`
- Create: `app/Models/Jabatan.php`

- [ ] **Step 1: Buat migration**

```bash
php artisan make:migration create_jabatans_table
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
        Schema::create('jabatans', function (Blueprint $table) {
            $table->id();
            $table->string('nama_jabatan');
            $table->text('keterangan')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jabatans');
    }
};
```

- [ ] **Step 2: Buat model Jabatan**

`app/Models/Jabatan.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jabatan extends Model
{
    protected $fillable = ['nama_jabatan', 'keterangan', 'aktif'];

    protected $casts = ['aktif' => 'boolean'];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
```

- [ ] **Step 3: Jalankan migration**

```bash
php artisan migrate
```

Expected: `jabatans` table created successfully.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/*_create_jabatans_table.php app/Models/Jabatan.php
git commit -m "feat: add Jabatan model and migration"
```

---

### Task 2: StatusPegawai — migration + model

**Files:**
- Create: `database/migrations/*_create_status_pegawais_table.php`
- Create: `app/Models/StatusPegawai.php`

- [ ] **Step 1: Buat migration**

```bash
php artisan make:migration create_status_pegawais_table
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
        Schema::create('status_pegawais', function (Blueprint $table) {
            $table->id();
            $table->string('nama_status');
            $table->text('keterangan')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_pegawais');
    }
};
```

- [ ] **Step 2: Buat model StatusPegawai**

`app/Models/StatusPegawai.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StatusPegawai extends Model
{
    protected $fillable = ['nama_status', 'keterangan', 'aktif'];

    protected $casts = ['aktif' => 'boolean'];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
```

- [ ] **Step 3: Jalankan migration**

```bash
php artisan migrate
```

Expected: `status_pegawais` table created successfully.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/*_create_status_pegawais_table.php app/Models/StatusPegawai.php
git commit -m "feat: add StatusPegawai model and migration"
```

---

### Task 3: Tambah kolom ke employees + update Employee model

**Files:**
- Create: `database/migrations/*_add_fields_to_employees_table.php`
- Modify: `app/Models/Employee.php`

- [ ] **Step 1: Buat migration**

```bash
php artisan make:migration add_fields_to_employees_table
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
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('jabatan_id')->nullable()->after('tanggal_masuk')
                  ->constrained('jabatans')->nullOnDelete();
            $table->foreignId('status_pegawai_id')->nullable()->after('jabatan_id')
                  ->constrained('status_pegawais')->nullOnDelete();
            $table->enum('klaster', ['klaster_1', 'klaster_2', 'klaster_3', 'klaster_4', 'lintas_klaster'])
                  ->nullable()->after('status_pegawai_id');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['jabatan_id']);
            $table->dropForeign(['status_pegawai_id']);
            $table->dropColumn(['jabatan_id', 'status_pegawai_id', 'klaster']);
        });
    }
};
```

- [ ] **Step 2: Update Employee model**

`app/Models/Employee.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'user_id', 'nip', 'nama_lengkap', 'status_aktif', 'tanggal_masuk',
        'jabatan_id', 'status_pegawai_id', 'klaster',
    ];

    protected $casts = [
        'status_aktif'  => 'boolean',
        'tanggal_masuk' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }

    public function statusPegawai(): BelongsTo
    {
        return $this->belongsTo(StatusPegawai::class);
    }
}
```

- [ ] **Step 3: Jalankan migration**

```bash
php artisan migrate
```

Expected: 3 kolom ditambahkan ke tabel employees.

- [ ] **Step 4: Pastikan existing tests masih lulus**

```bash
php artisan test tests/Feature/Services/EmployeeServiceTest.php
```

Expected: 2 tests, 2 passed.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/*_add_fields_to_employees_table.php app/Models/Employee.php
git commit -m "feat: add jabatan, status_pegawai, klaster fields to employees"
```

---

### Task 4: Update EmployeeService — create() + update()

**Files:**
- Modify: `app/Services/EmployeeService.php`
- Modify: `tests/Feature/Services/EmployeeServiceTest.php`

- [ ] **Step 1: Tulis test yang gagal untuk update()**

Tambahkan ke `tests/Feature/Services/EmployeeServiceTest.php`:

```php
use App\Models\Jabatan;

// ...tambahkan dua method test ini ke class yang ada:

public function test_update_changes_employee_and_user_data(): void
{
    $user = User::create([
        'name' => 'Budi Lama', 'email' => 'budi@example.com',
        'password' => 'password', 'role' => 'pegawai',
    ]);
    $employee = Employee::create([
        'user_id' => $user->id, 'nip' => '001',
        'nama_lengkap' => 'Budi Lama', 'tanggal_masuk' => '2024-01-01',
    ]);

    $this->service->update($employee, [
        'nama_lengkap' => 'Budi Baru',
        'email'        => 'budibaru@example.com',
        'password'     => '',
        'nip'          => '001',
        'tanggal_masuk'=> '2024-01-01',
        'status_aktif' => true,
    ]);

    $this->assertDatabaseHas('employees', ['id' => $employee->id, 'nama_lengkap' => 'Budi Baru']);
    $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'budibaru@example.com']);
}

public function test_update_skips_password_when_empty(): void
{
    $user = User::create([
        'name' => 'Budi', 'email' => 'budi@example.com',
        'password' => bcrypt('password_lama'), 'role' => 'pegawai',
    ]);
    $employee = Employee::create([
        'user_id' => $user->id, 'nip' => '001',
        'nama_lengkap' => 'Budi', 'tanggal_masuk' => '2024-01-01',
    ]);

    $oldHash = $user->fresh()->password;

    $this->service->update($employee, [
        'nama_lengkap' => 'Budi',
        'email'        => 'budi@example.com',
        'password'     => '',
        'nip'          => '001',
        'tanggal_masuk'=> '2024-01-01',
        'status_aktif' => true,
    ]);

    $this->assertEquals($oldHash, $user->fresh()->password);
}
```

- [ ] **Step 2: Jalankan test — verifikasi gagal**

```bash
php artisan test tests/Feature/Services/EmployeeServiceTest.php --filter=test_update
```

Expected: FAIL — "Call to undefined method App\Services\EmployeeService::update()"

- [ ] **Step 3: Update EmployeeService**

`app/Services/EmployeeService.php`:

```php
<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EmployeeService
{
    public function create(array $data): Employee
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name'     => $data['nama_lengkap'],
                'email'    => $data['email'],
                'password' => $data['password'],
                'role'     => 'pegawai',
            ]);

            return Employee::create([
                'user_id'           => $user->id,
                'nip'               => $data['nip'],
                'nama_lengkap'      => $data['nama_lengkap'],
                'jabatan_id'        => $data['jabatan_id'] ?? null,
                'status_pegawai_id' => $data['status_pegawai_id'] ?? null,
                'klaster'           => $data['klaster'] ?? null,
                'status_aktif'      => $data['status_aktif'] ?? true,
                'tanggal_masuk'     => $data['tanggal_masuk'],
            ]);
        });
    }

    public function update(Employee $employee, array $data): Employee
    {
        return DB::transaction(function () use ($employee, $data) {
            $userUpdate = [
                'name'  => $data['nama_lengkap'],
                'email' => $data['email'],
            ];
            if (! empty($data['password'])) {
                $userUpdate['password'] = $data['password'];
            }
            $employee->user->update($userUpdate);

            $employee->update([
                'nip'               => $data['nip'],
                'nama_lengkap'      => $data['nama_lengkap'],
                'jabatan_id'        => $data['jabatan_id'] ?? null,
                'status_pegawai_id' => $data['status_pegawai_id'] ?? null,
                'klaster'           => $data['klaster'] ?? null,
                'status_aktif'      => $data['status_aktif'] ?? true,
                'tanggal_masuk'     => $data['tanggal_masuk'],
            ]);

            return $employee->fresh();
        });
    }
}
```

- [ ] **Step 4: Jalankan semua EmployeeService tests**

```bash
php artisan test tests/Feature/Services/EmployeeServiceTest.php
```

Expected: 4 tests, 4 passed.

- [ ] **Step 5: Commit**

```bash
git add app/Services/EmployeeService.php tests/Feature/Services/EmployeeServiceTest.php
git commit -m "feat: update EmployeeService — support new fields in create, add update()"
```

---

### Task 5: JabatanManager — Livewire component + view + test

**Files:**
- Create: `app/Livewire/Kepegawaian/JabatanManager.php`
- Create: `resources/views/livewire/kepegawaian/jabatan-manager.blade.php`
- Create: `tests/Feature/Livewire/Kepegawaian/JabatanManagerTest.php`

- [ ] **Step 1: Tulis test yang gagal**

Buat direktori: `tests/Feature/Livewire/Kepegawaian/`

`tests/Feature/Livewire/Kepegawaian/JabatanManagerTest.php`:

```php
<?php

namespace Tests\Feature\Livewire\Kepegawaian;

use App\Livewire\Kepegawaian\JabatanManager;
use App\Models\Employee;
use App\Models\Jabatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JabatanManagerTest extends TestCase
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
        Livewire::test(JabatanManager::class)->assertStatus(200);
    }

    public function test_can_add_jabatan(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(JabatanManager::class)
            ->set('nama_jabatan', 'Staff IT')
            ->set('keterangan', 'Tim Teknologi')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('jabatans', ['nama_jabatan' => 'Staff IT']);
    }

    public function test_save_validates_required_fields(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(JabatanManager::class)
            ->set('nama_jabatan', '')
            ->call('save')
            ->assertHasErrors(['nama_jabatan' => 'required']);
    }

    public function test_can_edit_jabatan(): void
    {
        $jabatan = Jabatan::create(['nama_jabatan' => 'Nama Lama', 'aktif' => true]);
        $this->actingAs($this->admin);

        Livewire::test(JabatanManager::class)
            ->call('edit', $jabatan->id)
            ->set('nama_jabatan', 'Nama Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('jabatans', ['id' => $jabatan->id, 'nama_jabatan' => 'Nama Baru']);
    }

    public function test_can_toggle_aktif(): void
    {
        $jabatan = Jabatan::create(['nama_jabatan' => 'Staff IT', 'aktif' => true]);
        $this->actingAs($this->admin);

        Livewire::test(JabatanManager::class)->call('toggleAktif', $jabatan->id);

        $this->assertFalse($jabatan->fresh()->aktif);
    }

    public function test_cannot_delete_jabatan_used_by_employee(): void
    {
        $jabatan = Jabatan::create(['nama_jabatan' => 'Staff IT', 'aktif' => true]);
        $user = User::factory()->create(['role' => 'pegawai']);
        Employee::create([
            'user_id' => $user->id, 'nip' => '001', 'nama_lengkap' => 'Test',
            'tanggal_masuk' => today(), 'jabatan_id' => $jabatan->id,
        ]);
        $this->actingAs($this->admin);

        Livewire::test(JabatanManager::class)->call('delete', $jabatan->id);

        $this->assertDatabaseHas('jabatans', ['id' => $jabatan->id]);
    }

    public function test_can_delete_unused_jabatan(): void
    {
        $jabatan = Jabatan::create(['nama_jabatan' => 'Staff IT', 'aktif' => true]);
        $this->actingAs($this->admin);

        Livewire::test(JabatanManager::class)->call('delete', $jabatan->id);

        $this->assertDatabaseMissing('jabatans', ['id' => $jabatan->id]);
    }
}
```

- [ ] **Step 2: Jalankan test — verifikasi gagal**

```bash
php artisan test tests/Feature/Livewire/Kepegawaian/JabatanManagerTest.php
```

Expected: FAIL — class JabatanManager not found.

- [ ] **Step 3: Buat Livewire component**

`app/Livewire/Kepegawaian/JabatanManager.php`:

```php
<?php

namespace App\Livewire\Kepegawaian;

use App\Models\Jabatan;
use Livewire\Component;

class JabatanManager extends Component
{
    public string $nama_jabatan = '';
    public string $keterangan = '';
    public bool $aktif = true;
    public bool $showForm = false;
    public ?int $editingId = null;

    protected $rules = [
        'nama_jabatan' => 'required|string|max:100',
        'keterangan'   => 'nullable|string',
        'aktif'        => 'boolean',
    ];

    public function save(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $this->validate();

        $payload = [
            'nama_jabatan' => $this->nama_jabatan,
            'keterangan'   => $this->keterangan ?: null,
            'aktif'        => $this->aktif,
        ];

        if ($this->editingId) {
            Jabatan::findOrFail($this->editingId)->update($payload);
            session()->flash('success', 'Jabatan berhasil diperbarui.');
        } else {
            Jabatan::create($payload);
            session()->flash('success', 'Jabatan berhasil ditambahkan.');
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $jabatan = Jabatan::findOrFail($id);
        $this->editingId    = $id;
        $this->nama_jabatan = $jabatan->nama_jabatan;
        $this->keterangan   = $jabatan->keterangan ?? '';
        $this->aktif        = $jabatan->aktif;
        $this->showForm     = true;
    }

    public function toggleAktif(int $id): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $jabatan = Jabatan::findOrFail($id);
        $jabatan->update(['aktif' => ! $jabatan->aktif]);
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $jabatan = Jabatan::findOrFail($id);

        if ($jabatan->employees()->exists()) {
            session()->flash('error', 'Jabatan tidak dapat dihapus karena masih digunakan pegawai.');
            return;
        }

        $jabatan->delete();
        session()->flash('success', 'Jabatan berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset(['nama_jabatan', 'keterangan', 'showForm', 'editingId']);
        $this->aktif = true;
    }

    public function render()
    {
        return view('livewire.kepegawaian.jabatan-manager', [
            'jabatans' => Jabatan::orderBy('nama_jabatan')->get(),
        ]);
    }
}
```

- [ ] **Step 4: Buat direktori dan view**

Buat direktori: `resources/views/livewire/kepegawaian/`

`resources/views/livewire/kepegawaian/jabatan-manager.blade.php`:

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
        <h2 class="text-lg font-semibold text-gray-800">Daftar Jabatan</h2>
        <button wire:click="$set('showForm', true)"
                class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
            + Tambah Jabatan
        </button>
    </div>

    @if($showForm)
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">
            {{ $editingId ? 'Edit Jabatan' : 'Tambah Jabatan Baru' }}
        </h3>
        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Nama Jabatan</label>
                <input wire:model="nama_jabatan" type="text" placeholder="Contoh: Staff IT"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('nama_jabatan') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
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
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Nama Jabatan</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Keterangan</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($jabatans as $jabatan)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $jabatan->nama_jabatan }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $jabatan->keterangan ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <button wire:click="toggleAktif({{ $jabatan->id }})"
                                class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $jabatan->aktif ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $jabatan->aktif ? 'Aktif' : 'Nonaktif' }}
                        </button>
                    </td>
                    <td class="px-4 py-3 text-right space-x-3">
                        <button wire:click="edit({{ $jabatan->id }})"
                                class="text-indigo-400 hover:text-indigo-600 text-xs">Edit</button>
                        <button @click="Swal.fire({
                                    title: 'Hapus Jabatan?',
                                    text: 'Jabatan &quot;{{ $jabatan->nama_jabatan }}&quot; akan dihapus permanen.',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#ef4444',
                                    cancelButtonColor: '#6b7280',
                                    confirmButtonText: 'Ya, Hapus',
                                    cancelButtonText: 'Batal',
                                }).then((result) => { if (result.isConfirmed) $wire.delete({{ $jabatan->id }}) })"
                                class="text-red-400 hover:text-red-600 text-xs">Hapus</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-gray-400 text-sm">Belum ada jabatan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
```

- [ ] **Step 5: Jalankan test**

```bash
php artisan test tests/Feature/Livewire/Kepegawaian/JabatanManagerTest.php
```

Expected: 6 tests, 6 passed.

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/Kepegawaian/JabatanManager.php \
        resources/views/livewire/kepegawaian/jabatan-manager.blade.php \
        tests/Feature/Livewire/Kepegawaian/JabatanManagerTest.php
git commit -m "feat: add JabatanManager Livewire component"
```

---

### Task 6: StatusPegawaiManager — Livewire component + view + test

**Files:**
- Create: `app/Livewire/Kepegawaian/StatusPegawaiManager.php`
- Create: `resources/views/livewire/kepegawaian/status-pegawai-manager.blade.php`
- Create: `tests/Feature/Livewire/Kepegawaian/StatusPegawaiManagerTest.php`

- [ ] **Step 1: Tulis test yang gagal**

`tests/Feature/Livewire/Kepegawaian/StatusPegawaiManagerTest.php`:

```php
<?php

namespace Tests\Feature\Livewire\Kepegawaian;

use App\Livewire\Kepegawaian\StatusPegawaiManager;
use App\Models\Employee;
use App\Models\StatusPegawai;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StatusPegawaiManagerTest extends TestCase
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
        Livewire::test(StatusPegawaiManager::class)->assertStatus(200);
    }

    public function test_can_add_status_pegawai(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(StatusPegawaiManager::class)
            ->set('nama_status', 'PNS')
            ->set('keterangan', 'Pegawai Negeri Sipil')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('status_pegawais', ['nama_status' => 'PNS']);
    }

    public function test_save_validates_required_fields(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(StatusPegawaiManager::class)
            ->set('nama_status', '')
            ->call('save')
            ->assertHasErrors(['nama_status' => 'required']);
    }

    public function test_can_edit_status_pegawai(): void
    {
        $status = StatusPegawai::create(['nama_status' => 'Nama Lama', 'aktif' => true]);
        $this->actingAs($this->admin);

        Livewire::test(StatusPegawaiManager::class)
            ->call('edit', $status->id)
            ->set('nama_status', 'Nama Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('status_pegawais', ['id' => $status->id, 'nama_status' => 'Nama Baru']);
    }

    public function test_can_toggle_aktif(): void
    {
        $status = StatusPegawai::create(['nama_status' => 'PNS', 'aktif' => true]);
        $this->actingAs($this->admin);

        Livewire::test(StatusPegawaiManager::class)->call('toggleAktif', $status->id);

        $this->assertFalse($status->fresh()->aktif);
    }

    public function test_cannot_delete_status_used_by_employee(): void
    {
        $status = StatusPegawai::create(['nama_status' => 'PNS', 'aktif' => true]);
        $user = User::factory()->create(['role' => 'pegawai']);
        Employee::create([
            'user_id' => $user->id, 'nip' => '001', 'nama_lengkap' => 'Test',
            'tanggal_masuk' => today(), 'status_pegawai_id' => $status->id,
        ]);
        $this->actingAs($this->admin);

        Livewire::test(StatusPegawaiManager::class)->call('delete', $status->id);

        $this->assertDatabaseHas('status_pegawais', ['id' => $status->id]);
    }

    public function test_can_delete_unused_status(): void
    {
        $status = StatusPegawai::create(['nama_status' => 'PNS', 'aktif' => true]);
        $this->actingAs($this->admin);

        Livewire::test(StatusPegawaiManager::class)->call('delete', $status->id);

        $this->assertDatabaseMissing('status_pegawais', ['id' => $status->id]);
    }
}
```

- [ ] **Step 2: Jalankan test — verifikasi gagal**

```bash
php artisan test tests/Feature/Livewire/Kepegawaian/StatusPegawaiManagerTest.php
```

Expected: FAIL — class StatusPegawaiManager not found.

- [ ] **Step 3: Buat Livewire component**

`app/Livewire/Kepegawaian/StatusPegawaiManager.php`:

```php
<?php

namespace App\Livewire\Kepegawaian;

use App\Models\StatusPegawai;
use Livewire\Component;

class StatusPegawaiManager extends Component
{
    public string $nama_status = '';
    public string $keterangan = '';
    public bool $aktif = true;
    public bool $showForm = false;
    public ?int $editingId = null;

    protected $rules = [
        'nama_status' => 'required|string|max:100',
        'keterangan'  => 'nullable|string',
        'aktif'       => 'boolean',
    ];

    public function save(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $this->validate();

        $payload = [
            'nama_status' => $this->nama_status,
            'keterangan'  => $this->keterangan ?: null,
            'aktif'       => $this->aktif,
        ];

        if ($this->editingId) {
            StatusPegawai::findOrFail($this->editingId)->update($payload);
            session()->flash('success', 'Status pegawai berhasil diperbarui.');
        } else {
            StatusPegawai::create($payload);
            session()->flash('success', 'Status pegawai berhasil ditambahkan.');
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $status = StatusPegawai::findOrFail($id);
        $this->editingId   = $id;
        $this->nama_status = $status->nama_status;
        $this->keterangan  = $status->keterangan ?? '';
        $this->aktif       = $status->aktif;
        $this->showForm    = true;
    }

    public function toggleAktif(int $id): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $status = StatusPegawai::findOrFail($id);
        $status->update(['aktif' => ! $status->aktif]);
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $status = StatusPegawai::findOrFail($id);

        if ($status->employees()->exists()) {
            session()->flash('error', 'Status pegawai tidak dapat dihapus karena masih digunakan pegawai.');
            return;
        }

        $status->delete();
        session()->flash('success', 'Status pegawai berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset(['nama_status', 'keterangan', 'showForm', 'editingId']);
        $this->aktif = true;
    }

    public function render()
    {
        return view('livewire.kepegawaian.status-pegawai-manager', [
            'statusList' => StatusPegawai::orderBy('nama_status')->get(),
        ]);
    }
}
```

- [ ] **Step 4: Buat view**

`resources/views/livewire/kepegawaian/status-pegawai-manager.blade.php`:

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
```

- [ ] **Step 5: Jalankan test**

```bash
php artisan test tests/Feature/Livewire/Kepegawaian/StatusPegawaiManagerTest.php
```

Expected: 6 tests, 6 passed.

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/Kepegawaian/StatusPegawaiManager.php \
        resources/views/livewire/kepegawaian/status-pegawai-manager.blade.php \
        tests/Feature/Livewire/Kepegawaian/StatusPegawaiManagerTest.php
git commit -m "feat: add StatusPegawaiManager Livewire component"
```

---

### Task 7: EmployeeManager — Livewire component + view + test

**Files:**
- Create: `app/Livewire/Kepegawaian/EmployeeManager.php`
- Create: `resources/views/livewire/kepegawaian/employee-manager.blade.php`
- Create: `tests/Feature/Livewire/Kepegawaian/EmployeeManagerTest.php`

- [ ] **Step 1: Tulis test yang gagal**

`tests/Feature/Livewire/Kepegawaian/EmployeeManagerTest.php`:

```php
<?php

namespace Tests\Feature\Livewire\Kepegawaian;

use App\Livewire\Kepegawaian\EmployeeManager;
use App\Models\Employee;
use App\Models\Jabatan;
use App\Models\StatusPegawai;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Jabatan $jabatan;
    private StatusPegawai $statusPegawai;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin         = User::factory()->create(['role' => 'admin']);
        $this->jabatan       = Jabatan::create(['nama_jabatan' => 'Staff', 'aktif' => true]);
        $this->statusPegawai = StatusPegawai::create(['nama_status' => 'PNS', 'aktif' => true]);
    }

    public function test_component_renders(): void
    {
        $this->actingAs($this->admin);
        Livewire::test(EmployeeManager::class)->assertStatus(200);
    }

    public function test_can_add_employee(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(EmployeeManager::class)
            ->set('nama_lengkap', 'Budi Santoso')
            ->set('email', 'budi@example.com')
            ->set('password', 'password123')
            ->set('nip', '001')
            ->set('tanggal_masuk', '2024-01-01')
            ->set('jabatan_id', $this->jabatan->id)
            ->set('status_pegawai_id', $this->statusPegawai->id)
            ->set('klaster', 'klaster_1')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employees', ['nip' => '001', 'jabatan_id' => $this->jabatan->id]);
        $this->assertDatabaseHas('users', ['email' => 'budi@example.com', 'role' => 'pegawai']);
    }

    public function test_save_validates_required_fields(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(EmployeeManager::class)
            ->set('nama_lengkap', '')
            ->set('email', '')
            ->set('password', '')
            ->set('nip', '')
            ->set('tanggal_masuk', '')
            ->call('save')
            ->assertHasErrors(['nama_lengkap', 'email', 'password', 'nip', 'tanggal_masuk']);
    }

    public function test_can_edit_employee_without_changing_password(): void
    {
        $user = User::factory()->create(['role' => 'pegawai', 'email' => 'old@example.com']);
        $employee = Employee::create([
            'user_id' => $user->id, 'nip' => '001', 'nama_lengkap' => 'Nama Lama',
            'tanggal_masuk' => today(),
        ]);
        $oldHash = $user->fresh()->password;
        $this->actingAs($this->admin);

        Livewire::test(EmployeeManager::class)
            ->call('edit', $employee->id)
            ->set('nama_lengkap', 'Nama Baru')
            ->set('password', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'nama_lengkap' => 'Nama Baru']);
        $this->assertEquals($oldHash, $user->fresh()->password);
    }

    public function test_can_toggle_status_aktif(): void
    {
        $user = User::factory()->create(['role' => 'pegawai']);
        $employee = Employee::create([
            'user_id' => $user->id, 'nip' => '001', 'nama_lengkap' => 'Test',
            'tanggal_masuk' => today(), 'status_aktif' => true,
        ]);
        $this->actingAs($this->admin);

        Livewire::test(EmployeeManager::class)->call('toggleStatusAktif', $employee->id);

        $this->assertFalse($employee->fresh()->status_aktif);
    }

    public function test_can_delete_employee(): void
    {
        $user = User::factory()->create(['role' => 'pegawai']);
        $employee = Employee::create([
            'user_id' => $user->id, 'nip' => '001', 'nama_lengkap' => 'Test',
            'tanggal_masuk' => today(),
        ]);
        $this->actingAs($this->admin);

        Livewire::test(EmployeeManager::class)->call('delete', $employee->id);

        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
    }

    public function test_invalid_klaster_fails_validation(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(EmployeeManager::class)
            ->set('nama_lengkap', 'Budi')
            ->set('email', 'budi@example.com')
            ->set('password', 'password123')
            ->set('nip', '001')
            ->set('tanggal_masuk', '2024-01-01')
            ->set('klaster', 'klaster_99')
            ->call('save')
            ->assertHasErrors(['klaster']);
    }
}
```

- [ ] **Step 2: Jalankan test — verifikasi gagal**

```bash
php artisan test tests/Feature/Livewire/Kepegawaian/EmployeeManagerTest.php
```

Expected: FAIL — class EmployeeManager not found.

- [ ] **Step 3: Buat Livewire component**

`app/Livewire/Kepegawaian/EmployeeManager.php`:

```php
<?php

namespace App\Livewire\Kepegawaian;

use App\Models\Employee;
use App\Models\Jabatan;
use App\Models\StatusPegawai;
use App\Services\EmployeeService;
use Livewire\Component;

class EmployeeManager extends Component
{
    public const KLASTER_OPTIONS = [
        'klaster_1'      => 'Klaster 1',
        'klaster_2'      => 'Klaster 2',
        'klaster_3'      => 'Klaster 3',
        'klaster_4'      => 'Klaster 4',
        'lintas_klaster' => 'Lintas Klaster',
    ];

    public string $nama_lengkap = '';
    public string $email = '';
    public string $password = '';
    public string $nip = '';
    public string $tanggal_masuk = '';
    public ?int $jabatan_id = null;
    public ?int $status_pegawai_id = null;
    public ?string $klaster = null;
    public bool $status_aktif = true;
    public bool $showForm = false;
    public ?int $editingId = null;
    public ?int $editingUserId = null;

    public function rules(): array
    {
        $nipRule   = 'required|string|max:20|unique:employees,nip' . ($this->editingId ? ',' . $this->editingId : '');
        $emailRule = 'required|email|unique:users,email' . ($this->editingUserId ? ',' . $this->editingUserId : '');
        $pwRule    = $this->editingId ? 'nullable|string|min:8' : 'required|string|min:8';

        return [
            'nama_lengkap'      => 'required|string|max:255',
            'email'             => $emailRule,
            'password'          => $pwRule,
            'nip'               => $nipRule,
            'tanggal_masuk'     => 'required|date',
            'jabatan_id'        => 'nullable|exists:jabatans,id',
            'status_pegawai_id' => 'nullable|exists:status_pegawais,id',
            'klaster'           => 'nullable|in:klaster_1,klaster_2,klaster_3,klaster_4,lintas_klaster',
            'status_aktif'      => 'boolean',
        ];
    }

    public function save(EmployeeService $service): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $this->validate();

        $data = [
            'nama_lengkap'      => $this->nama_lengkap,
            'email'             => $this->email,
            'password'          => $this->password,
            'nip'               => $this->nip,
            'tanggal_masuk'     => $this->tanggal_masuk,
            'jabatan_id'        => $this->jabatan_id ?: null,
            'status_pegawai_id' => $this->status_pegawai_id ?: null,
            'klaster'           => $this->klaster ?: null,
            'status_aktif'      => $this->status_aktif,
        ];

        if ($this->editingId) {
            $service->update(Employee::findOrFail($this->editingId), $data);
            session()->flash('success', 'Data pegawai berhasil diperbarui.');
        } else {
            $service->create($data);
            session()->flash('success', 'Pegawai berhasil ditambahkan.');
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $employee = Employee::with('user')->findOrFail($id);
        $this->editingId         = $id;
        $this->editingUserId     = $employee->user_id;
        $this->nama_lengkap      = $employee->nama_lengkap;
        $this->email             = $employee->user->email;
        $this->password          = '';
        $this->nip               = $employee->nip;
        $this->tanggal_masuk     = $employee->tanggal_masuk->format('Y-m-d');
        $this->jabatan_id        = $employee->jabatan_id;
        $this->status_pegawai_id = $employee->status_pegawai_id;
        $this->klaster           = $employee->klaster;
        $this->status_aktif      = $employee->status_aktif;
        $this->showForm          = true;
    }

    public function toggleStatusAktif(int $id): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $employee = Employee::findOrFail($id);
        $employee->update(['status_aktif' => ! $employee->status_aktif]);
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        Employee::findOrFail($id)->delete();
        session()->flash('success', 'Pegawai berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset([
            'nama_lengkap', 'email', 'password', 'nip', 'tanggal_masuk',
            'jabatan_id', 'status_pegawai_id', 'klaster',
            'showForm', 'editingId', 'editingUserId',
        ]);
        $this->status_aktif = true;
    }

    public function render()
    {
        return view('livewire.kepegawaian.employee-manager', [
            'employees'      => Employee::with(['user', 'jabatan', 'statusPegawai'])->orderBy('nama_lengkap')->get(),
            'jabatans'       => Jabatan::where('aktif', true)->orderBy('nama_jabatan')->get(),
            'statusPegawais' => StatusPegawai::where('aktif', true)->orderBy('nama_status')->get(),
            'klasterOptions' => self::KLASTER_OPTIONS,
        ]);
    }
}
```

- [ ] **Step 4: Buat view**

`resources/views/livewire/kepegawaian/employee-manager.blade.php`:

```html
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
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">NIP</th>
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
                    <td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">Belum ada pegawai.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
```

- [ ] **Step 5: Jalankan test**

```bash
php artisan test tests/Feature/Livewire/Kepegawaian/EmployeeManagerTest.php
```

Expected: 6 tests, 6 passed.

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/Kepegawaian/EmployeeManager.php \
        resources/views/livewire/kepegawaian/employee-manager.blade.php \
        tests/Feature/Livewire/Kepegawaian/EmployeeManagerTest.php
git commit -m "feat: add EmployeeManager Livewire component"
```

---

### Task 8: Routes + page views + sidebar

**Files:**
- Modify: `routes/web.php`
- Create: `resources/views/pages/kepegawaian/employees.blade.php`
- Create: `resources/views/pages/kepegawaian/jabatan.blade.php`
- Create: `resources/views/pages/kepegawaian/status-pegawai.blade.php`
- Modify: `resources/views/components/sidebar.blade.php`

- [ ] **Step 1: Tambah routes kepegawaian ke `routes/web.php`**

Tambahkan di dalam block `Route::prefix('admin')...`:

```php
// Dalam group: Route::prefix('admin')->name('admin.')->middleware('role:admin')
Route::prefix('kepegawaian')->name('kepegawaian.')->group(function () {
    Route::get('/', fn () => view('pages.kepegawaian.employees'))->name('employees');
    Route::get('/jabatan', fn () => view('pages.kepegawaian.jabatan'))->name('jabatan');
    Route::get('/status-pegawai', fn () => view('pages.kepegawaian.status-pegawai'))->name('status-pegawai');
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

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/absensi', fn () => view('pages.attendance.admin'))->name('attendance.index');
        Route::get('/pengaturan-jam', fn () => view('pages.settings.shifts'))->name('settings.shifts');

        Route::prefix('kepegawaian')->name('kepegawaian.')->group(function () {
            Route::get('/', fn () => view('pages.kepegawaian.employees'))->name('employees');
            Route::get('/jabatan', fn () => view('pages.kepegawaian.jabatan'))->name('jabatan');
            Route::get('/status-pegawai', fn () => view('pages.kepegawaian.status-pegawai'))->name('status-pegawai');
        });
    });
});

require __DIR__ . '/auth.php';
```

- [ ] **Step 2: Buat page views kepegawaian**

Buat direktori: `resources/views/pages/kepegawaian/`

`resources/views/pages/kepegawaian/employees.blade.php`:
```html
<x-layouts.app title="Data Pegawai">
    <livewire:kepegawaian.employee-manager />
</x-layouts.app>
```

`resources/views/pages/kepegawaian/jabatan.blade.php`:
```html
<x-layouts.app title="Jabatan">
    <livewire:kepegawaian.jabatan-manager />
</x-layouts.app>
```

`resources/views/pages/kepegawaian/status-pegawai.blade.php`:
```html
<x-layouts.app title="Status Pegawai">
    <livewire:kepegawaian.status-pegawai-manager />
</x-layouts.app>
```

- [ ] **Step 3: Update sidebar**

Ganti isi `resources/views/components/sidebar.blade.php` dengan versi berikut. Perubahan: (a) hapus "Kepegawaian" dari daftar "Segera Hadir", (b) tambah submenu Kepegawaian collapsible di section Admin:

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
                {{-- Link ini muncul otomatis setelah plan Hari Libur dijalankan --}}
                @if(Route::has('admin.settings.holidays'))
                <a href="{{ route('admin.settings.holidays') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.settings.holidays') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Hari Libur
                </a>
                @endif

                {{-- Kepegawaian collapsible submenu --}}
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
        resources/views/pages/kepegawaian/ \
        resources/views/components/sidebar.blade.php
git commit -m "feat: add kepegawaian routes, page views, and sidebar submenu"
```
