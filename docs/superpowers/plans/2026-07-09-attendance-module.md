# Personnel Management System — Attendance Module Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build core infrastructure + complete Attendance Module for myPuspa using the TALL stack on Laravel 13.

**Architecture:** Service Layer pattern — Livewire components delegate business logic to `AttendanceService` and `EmployeeService`. Role-based access (`admin`/`pegawai`) enforced via `EnsureUserHasRole` middleware. Auth scaffolded by Laravel Breeze (Livewire stack).

**Tech Stack:** Laravel 13.19 · Livewire 3 · Tailwind CSS 4 · Alpine.js · MySQL

---

## File Map

**Created:**
- `database/migrations/*_add_role_to_users_table.php`
- `database/migrations/*_create_employees_table.php`
- `database/migrations/*_create_attendance_settings_table.php`
- `database/migrations/*_create_attendances_table.php`
- `app/Models/Employee.php`
- `app/Models/Attendance.php`
- `app/Models/AttendanceSetting.php`
- `app/Http/Middleware/EnsureUserHasRole.php`
- `app/Services/AttendanceService.php`
- `app/Services/EmployeeService.php`
- `app/Livewire/Attendance/ClockInOut.php`
- `app/Livewire/Attendance/AttendanceHistory.php`
- `app/Livewire/Attendance/AdminAttendanceTable.php`
- `app/Livewire/Settings/ShiftManager.php`
- `resources/views/components/sidebar.blade.php`
- `resources/views/livewire/attendance/clock-in-out.blade.php`
- `resources/views/livewire/attendance/attendance-history.blade.php`
- `resources/views/livewire/attendance/admin-attendance-table.blade.php`
- `resources/views/livewire/settings/shift-manager.blade.php`
- `resources/views/pages/dashboard.blade.php`
- `resources/views/pages/attendance/clock-in.blade.php`
- `resources/views/pages/attendance/history.blade.php`
- `resources/views/pages/attendance/admin.blade.php`
- `resources/views/pages/settings/shifts.blade.php`
- `database/seeders/AttendanceSettingSeeder.php`
- `database/seeders/UserEmployeeSeeder.php`
- `tests/Feature/Services/AttendanceServiceTest.php`
- `tests/Feature/Services/EmployeeServiceTest.php`
- `tests/Feature/Livewire/ClockInOutTest.php`
- `tests/Feature/Livewire/ShiftManagerTest.php`
- `tests/Feature/Livewire/AdminAttendanceTableTest.php`

**Modified:**
- `app/Models/User.php`
- `routes/web.php`
- `bootstrap/app.php`
- `database/seeders/DatabaseSeeder.php`
- `resources/views/components/layouts/app.blade.php` (created by Breeze, then replaced)

---

## Task 1: Install Packages & Configure Environment

**Files:** composer.json, package.json (via install commands)

- [ ] **Step 1: Ensure MySQL database exists**

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS laravel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

- [ ] **Step 2: Install Laravel Breeze with Livewire stack**

```bash
composer require laravel/breeze --dev
php artisan breeze:install livewire
```

Accept all defaults. This installs Livewire 3, Volt, Alpine.js, and scaffolds auth views and layouts.

- [ ] **Step 3: Install npm dependencies and build**

```bash
npm install
npm run build
```

Expected: `public/build/` created, no errors.

- [ ] **Step 4: Run default migrations**

```bash
php artisan migrate
```

Expected: users, cache, jobs, password_reset_tokens tables created.

- [ ] **Step 5: Initialize git and commit baseline**

```bash
git init
git add -A
git commit -m "chore: fresh Laravel 13 + Breeze Livewire stack"
```

---

## Task 2: Database Migrations

**Files:** 4 new migration files in `database/migrations/`

- [ ] **Step 1: Generate migration stubs**

```bash
php artisan make:migration add_role_to_users_table --table=users
php artisan make:migration create_employees_table
php artisan make:migration create_attendance_settings_table
php artisan make:migration create_attendances_table
```

- [ ] **Step 2: Fill add_role_to_users_table**

Find the file ending in `_add_role_to_users_table.php` and replace `up()` and `down()`:

```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->enum('role', ['admin', 'pegawai'])->default('pegawai')->after('email');
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('role');
    });
}
```

- [ ] **Step 3: Fill create_employees_table**

```php
public function up(): void
{
    Schema::create('employees', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
        $table->string('nip', 20)->unique();
        $table->string('nama_lengkap');
        $table->boolean('status_aktif')->default(true);
        $table->date('tanggal_masuk');
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('employees');
}
```

- [ ] **Step 4: Fill create_attendance_settings_table**

```php
public function up(): void
{
    Schema::create('attendance_settings', function (Blueprint $table) {
        $table->id();
        $table->string('nama_shift', 100);
        $table->time('jam_masuk_mulai');
        $table->time('jam_masuk_selesai');
        $table->time('jam_pulang_mulai');
        $table->boolean('status_aktif')->default(true);
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('attendance_settings');
}
```

- [ ] **Step 5: Fill create_attendances_table**

```php
public function up(): void
{
    Schema::create('attendances', function (Blueprint $table) {
        $table->id();
        $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
        $table->date('tanggal');
        $table->time('jam_masuk')->nullable();
        $table->time('jam_pulang')->nullable();
        $table->enum('status_kehadiran', ['hadir', 'izin', 'sakit', 'alfa'])->default('alfa');
        $table->text('lokasi_masuk')->nullable();
        $table->text('lokasi_pulang')->nullable();
        $table->text('keterangan')->nullable();
        $table->timestamps();
        $table->unique(['employee_id', 'tanggal']);
    });
}

public function down(): void
{
    Schema::dropIfExists('attendances');
}
```

- [ ] **Step 6: Run migrations**

```bash
php artisan migrate
```

Expected: 4 new migrations run. No errors.

- [ ] **Step 7: Commit**

```bash
git add database/migrations/
git commit -m "feat: add employees, attendances, attendance_settings migrations"
```

---

## Task 3: Models & Eloquent Relationships

**Files:** `app/Models/User.php`, `app/Models/Employee.php`, `app/Models/Attendance.php`, `app/Models/AttendanceSetting.php`

- [ ] **Step 1: Update User model**

Replace `app/Models/User.php` entirely:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

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

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
```

- [ ] **Step 2: Create Employee model**

```bash
php artisan make:model Employee
```

Replace `app/Models/Employee.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = ['user_id', 'nip', 'nama_lengkap', 'status_aktif', 'tanggal_masuk'];

    protected $casts = [
        'status_aktif' => 'boolean',
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
}
```

- [ ] **Step 3: Create Attendance model**

```bash
php artisan make:model Attendance
```

Replace `app/Models/Attendance.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id', 'tanggal', 'jam_masuk', 'jam_pulang',
        'status_kehadiran', 'lokasi_masuk', 'lokasi_pulang', 'keterangan',
    ];

    protected $casts = ['tanggal' => 'date'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
```

- [ ] **Step 4: Create AttendanceSetting model**

```bash
php artisan make:model AttendanceSetting
```

Replace `app/Models/AttendanceSetting.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSetting extends Model
{
    protected $fillable = [
        'nama_shift', 'jam_masuk_mulai', 'jam_masuk_selesai',
        'jam_pulang_mulai', 'status_aktif',
    ];

    protected $casts = ['status_aktif' => 'boolean'];
}
```

- [ ] **Step 5: Commit**

```bash
git add app/Models/
git commit -m "feat: add Employee, Attendance, AttendanceSetting models with relationships"
```

---

## Task 4: Middleware & Routes

**Files:** `app/Http/Middleware/EnsureUserHasRole.php`, `bootstrap/app.php`, `routes/web.php`

- [ ] **Step 1: Create middleware**

Create `app/Http/Middleware/EnsureUserHasRole.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user() || ! in_array($request->user()->role, $roles)) {
            abort(403, 'Akses ditolak.');
        }

        return $next($request);
    }
}
```

- [ ] **Step 2: Register middleware alias in bootstrap/app.php**

Inside the `->withMiddleware(function (Middleware $middleware) {` closure, add:

```php
$middleware->alias([
    'role' => \App\Http\Middleware\EnsureUserHasRole::class,
]);
```

- [ ] **Step 3: Replace routes/web.php**

```php
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', fn () => view('pages.dashboard'))->name('dashboard');

    // Pegawai only
    Route::middleware('role:pegawai')->group(function () {
        Route::get('/absensi/clock-in', fn () => view('pages.attendance.clock-in'))->name('attendance.clock-in');
    });

    // Both roles
    Route::get('/absensi/riwayat', fn () => view('pages.attendance.history'))->name('attendance.history');

    // Admin only
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/absensi', fn () => view('pages.attendance.admin'))->name('attendance.index');
        Route::get('/pengaturan-jam', fn () => view('pages.settings.shifts'))->name('settings.shifts');
    });
});

require __DIR__ . '/auth.php';
```

- [ ] **Step 4: Verify routes**

```bash
php artisan route:list --path=admin
```

Expected: Shows `admin/absensi` and `admin/pengaturan-jam` routes.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Middleware/ bootstrap/app.php routes/web.php
git commit -m "feat: add role middleware and route groups"
```

---

## Task 5: EmployeeService (TDD)

**Files:** `app/Services/EmployeeService.php`, `tests/Feature/Services/EmployeeServiceTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Services/EmployeeServiceTest.php`:

```php
<?php

namespace Tests\Feature\Services;

use App\Models\Employee;
use App\Models\User;
use App\Services\EmployeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeServiceTest extends TestCase
{
    use RefreshDatabase;

    private EmployeeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EmployeeService();
    }

    public function test_create_makes_user_and_employee_in_transaction(): void
    {
        $employee = $this->service->create([
            'nama_lengkap' => 'Budi Santoso',
            'email'        => 'budi@example.com',
            'password'     => 'password',
            'nip'          => '1234567890',
            'tanggal_masuk' => '2024-01-01',
        ]);

        $this->assertInstanceOf(Employee::class, $employee);
        $this->assertDatabaseHas('employees', ['nip' => '1234567890']);
        $this->assertDatabaseHas('users', ['email' => 'budi@example.com', 'role' => 'pegawai']);
        $this->assertEquals('budi@example.com', $employee->user->email);
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```bash
php artisan test tests/Feature/Services/EmployeeServiceTest.php
```

Expected: Error — `EmployeeService` not found.

- [ ] **Step 3: Implement EmployeeService**

Create `app/Services/EmployeeService.php`:

```php
<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmployeeService
{
    public function create(array $data): Employee
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name'     => $data['nama_lengkap'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
                'role'     => 'pegawai',
            ]);

            return Employee::create([
                'user_id'      => $user->id,
                'nip'          => $data['nip'],
                'nama_lengkap' => $data['nama_lengkap'],
                'status_aktif' => $data['status_aktif'] ?? true,
                'tanggal_masuk' => $data['tanggal_masuk'],
            ]);
        });
    }
}
```

- [ ] **Step 4: Run test — expect PASS**

```bash
php artisan test tests/Feature/Services/EmployeeServiceTest.php
```

Expected: 1 test, 1 passed.

- [ ] **Step 5: Commit**

```bash
git add app/Services/EmployeeService.php tests/Feature/Services/EmployeeServiceTest.php
git commit -m "feat: add EmployeeService with transactional user+employee creation"
```

---

## Task 6: AttendanceService (TDD)

**Files:** `app/Services/AttendanceService.php`, `tests/Feature/Services/AttendanceServiceTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/Services/AttendanceServiceTest.php`:

```php
<?php

namespace Tests\Feature\Services;

use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Employee;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private AttendanceService $service;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AttendanceService();

        $user = User::factory()->create(['role' => 'pegawai']);
        $this->employee = Employee::create([
            'user_id'      => $user->id,
            'nip'          => '111',
            'nama_lengkap' => 'Test Pegawai',
            'status_aktif' => true,
            'tanggal_masuk' => today(),
        ]);

        AttendanceSetting::create([
            'nama_shift'        => 'Shift Pagi',
            'jam_masuk_mulai'   => '07:00:00',
            'jam_masuk_selesai' => '08:00:00',
            'jam_pulang_mulai'  => '16:00:00',
            'status_aktif'      => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_clock_in_creates_hadir_record(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(7, 30));

        $attendance = $this->service->clockIn($this->employee, 'Kantor Pusat');

        $this->assertDatabaseHas('attendances', [
            'employee_id'      => $this->employee->id,
            'status_kehadiran' => 'hadir',
        ]);
        $this->assertNull($attendance->keterangan);
    }

    public function test_clock_in_marks_terlambat_after_threshold(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 30));

        $attendance = $this->service->clockIn($this->employee, 'Kantor Pusat');

        $this->assertEquals('Terlambat', $attendance->keterangan);
    }

    public function test_clock_in_throws_when_already_clocked_in(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(7, 30));
        $this->service->clockIn($this->employee, 'Kantor Pusat');

        $this->expectException(\RuntimeException::class);
        $this->service->clockIn($this->employee, 'Kantor Pusat');
    }

    public function test_clock_out_sets_jam_pulang(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(7, 30));
        $attendance = $this->service->clockIn($this->employee, 'Kantor Pusat');

        Carbon::setTestNow(Carbon::today()->setTime(16, 0));
        $result = $this->service->clockOut($attendance, 'Kantor Pusat');

        $this->assertNotNull($result->jam_pulang);
        $this->assertEquals('Kantor Pusat', $result->lokasi_pulang);
    }

    public function test_clock_out_throws_when_already_clocked_out(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(7, 30));
        $attendance = $this->service->clockIn($this->employee, 'Kantor');
        Carbon::setTestNow(Carbon::today()->setTime(16, 0));
        $this->service->clockOut($attendance, 'Kantor');

        $this->expectException(\RuntimeException::class);
        $this->service->clockOut($attendance->fresh(), 'Kantor');
    }
}
```

- [ ] **Step 2: Run tests — expect FAIL**

```bash
php artisan test tests/Feature/Services/AttendanceServiceTest.php
```

Expected: Error — `AttendanceService` not found.

- [ ] **Step 3: Implement AttendanceService**

Create `app/Services/AttendanceService.php`:

```php
<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Employee;
use Carbon\Carbon;

class AttendanceService
{
    public function clockIn(Employee $employee, string $lokasi): Attendance
    {
        if (Attendance::where('employee_id', $employee->id)->whereDate('tanggal', today())->exists()) {
            throw new \RuntimeException('Sudah melakukan absen masuk hari ini.');
        }

        $setting = AttendanceSetting::where('status_aktif', true)->firstOrFail();
        $now = Carbon::now();
        $keterangan = $now->toTimeString() > $setting->jam_masuk_selesai ? 'Terlambat' : null;

        return Attendance::create([
            'employee_id'      => $employee->id,
            'tanggal'          => today(),
            'jam_masuk'        => $now->toTimeString(),
            'status_kehadiran' => 'hadir',
            'lokasi_masuk'     => $lokasi,
            'keterangan'       => $keterangan,
        ]);
    }

    public function clockOut(Attendance $attendance, string $lokasi): Attendance
    {
        if (! $attendance->jam_masuk || $attendance->jam_pulang) {
            throw new \RuntimeException('Tidak dapat melakukan absen pulang saat ini.');
        }

        $attendance->update([
            'jam_pulang'    => Carbon::now()->toTimeString(),
            'lokasi_pulang' => $lokasi,
        ]);

        return $attendance->fresh();
    }

    public function todayAttendance(Employee $employee): ?Attendance
    {
        return Attendance::where('employee_id', $employee->id)
            ->whereDate('tanggal', today())
            ->first();
    }
}
```

- [ ] **Step 4: Run tests — expect PASS**

```bash
php artisan test tests/Feature/Services/AttendanceServiceTest.php
```

Expected: 5 tests, 5 passed.

- [ ] **Step 5: Commit**

```bash
git add app/Services/AttendanceService.php tests/Feature/Services/AttendanceServiceTest.php
git commit -m "feat: add AttendanceService with clock-in/out and late detection"
```

---

## Task 7: Seeders

**Files:** `database/seeders/AttendanceSettingSeeder.php`, `database/seeders/UserEmployeeSeeder.php`, `database/seeders/DatabaseSeeder.php`

- [ ] **Step 1: Generate seeders**

```bash
php artisan make:seeder AttendanceSettingSeeder
php artisan make:seeder UserEmployeeSeeder
```

- [ ] **Step 2: Write AttendanceSettingSeeder**

Replace `database/seeders/AttendanceSettingSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\AttendanceSetting;
use Illuminate\Database\Seeder;

class AttendanceSettingSeeder extends Seeder
{
    public function run(): void
    {
        AttendanceSetting::create([
            'nama_shift'        => 'Shift Reguler',
            'jam_masuk_mulai'   => '07:00:00',
            'jam_masuk_selesai' => '08:00:00',
            'jam_pulang_mulai'  => '16:00:00',
            'status_aktif'      => true,
        ]);
    }
}
```

- [ ] **Step 3: Write UserEmployeeSeeder**

Replace `database/seeders/UserEmployeeSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'     => 'Administrator',
            'email'    => 'admin@puspa.test',
            'password' => Hash::make('password'),
            'role'     => 'admin',
        ]);

        $pegawai = [
            ['nip' => '19850101001', 'nama_lengkap' => 'Siti Rahayu',      'email' => 'siti@puspa.test',   'tanggal_masuk' => '2020-03-01'],
            ['nip' => '19880215002', 'nama_lengkap' => 'Ahmad Fauzi',      'email' => 'ahmad@puspa.test',  'tanggal_masuk' => '2019-07-15'],
            ['nip' => '19900522003', 'nama_lengkap' => 'Dewi Lestari',     'email' => 'dewi@puspa.test',   'tanggal_masuk' => '2021-01-10'],
            ['nip' => '19921130004', 'nama_lengkap' => 'Rizki Pratama',    'email' => 'rizki@puspa.test',  'tanggal_masuk' => '2022-06-01'],
            ['nip' => '19950807005', 'nama_lengkap' => 'Putri Handayani',  'email' => 'putri@puspa.test',  'tanggal_masuk' => '2023-02-20'],
        ];

        foreach ($pegawai as $data) {
            $user = User::create([
                'name'     => $data['nama_lengkap'],
                'email'    => $data['email'],
                'password' => Hash::make('password'),
                'role'     => 'pegawai',
            ]);

            Employee::create([
                'user_id'      => $user->id,
                'nip'          => $data['nip'],
                'nama_lengkap' => $data['nama_lengkap'],
                'status_aktif' => true,
                'tanggal_masuk' => $data['tanggal_masuk'],
            ]);
        }
    }
}
```

- [ ] **Step 4: Update DatabaseSeeder**

Replace `database/seeders/DatabaseSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AttendanceSettingSeeder::class,
            UserEmployeeSeeder::class,
        ]);
    }
}
```

- [ ] **Step 5: Run and verify**

```bash
php artisan db:seed
php artisan tinker --execute="echo 'Users: ' . App\Models\User::count() . ', Employees: ' . App\Models\Employee::count();"
```

Expected: `Users: 6, Employees: 5`

- [ ] **Step 6: Commit**

```bash
git add database/seeders/
git commit -m "feat: add seeders for admin, 5 dummy pegawai, and default shift"
```

---

## Task 8: App Layout & Sidebar

**Files:** `resources/views/components/layouts/app.blade.php`, `resources/views/components/sidebar.blade.php`, page stubs

- [ ] **Step 1: Replace app layout**

After Breeze install, `resources/views/components/layouts/app.blade.php` exists. Replace it entirely:

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'myPuspa') }} — {{ $title ?? 'Dashboard' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-50 min-h-screen">
    <x-sidebar />
    <div class="pl-64 min-h-screen flex flex-col">
        <header class="h-16 bg-white border-b border-gray-200 flex items-center px-6">
            <h1 class="text-lg font-semibold text-gray-800">{{ $title ?? 'Dashboard' }}</h1>
        </header>
        <main class="flex-1 p-6">
            {{ $slot }}
        </main>
    </div>
    @livewireScripts
</body>
</html>
```

- [ ] **Step 2: Create sidebar component**

Create `resources/views/components/sidebar.blade.php`:

```blade
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
            <a href="{{ route('attendance.history') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('attendance.history') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Riwayat Absensi
            </a>
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
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.settings.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Pengaturan Jam Kerja
                </a>
            </div>
            @endif
        @endauth

        <div class="pt-4">
            <p class="px-3 text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">Segera Hadir</p>
            @foreach(['Kepegawaian', 'Penggajian', 'Penilaian Kinerja'] as $module)
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
</aside>
```

- [ ] **Step 3: Create page views**

Create `resources/views/pages/dashboard.blade.php`:

```blade
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
```

Create `resources/views/pages/attendance/clock-in.blade.php`:

```blade
<x-layouts.app title="Clock In / Out">
    <div class="max-w-xl mx-auto">
        <livewire:attendance.clock-in-out />
    </div>
</x-layouts.app>
```

Create `resources/views/pages/attendance/history.blade.php`:

```blade
<x-layouts.app title="Riwayat Absensi">
    <livewire:attendance.attendance-history />
</x-layouts.app>
```

Create `resources/views/pages/attendance/admin.blade.php`:

```blade
<x-layouts.app title="Monitor Absensi">
    <livewire:attendance.admin-attendance-table />
</x-layouts.app>
```

Create `resources/views/pages/settings/shifts.blade.php`:

```blade
<x-layouts.app title="Pengaturan Jam Kerja">
    <livewire:settings.shift-manager />
</x-layouts.app>
```

- [ ] **Step 4: Build assets**

```bash
npm run build
```

Expected: No errors.

- [ ] **Step 5: Commit**

```bash
git add resources/views/
git commit -m "feat: add sidebar layout and page views"
```

---

## Task 9: ShiftManager Livewire Component (TDD)

**Files:** `app/Livewire/Settings/ShiftManager.php`, `resources/views/livewire/settings/shift-manager.blade.php`, `tests/Feature/Livewire/ShiftManagerTest.php`

- [ ] **Step 1: Generate component**

```bash
php artisan make:livewire Settings/ShiftManager
```

- [ ] **Step 2: Write the failing tests**

Create `tests/Feature/Livewire/ShiftManagerTest.php`:

```php
<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Settings\ShiftManager;
use App\Models\AttendanceSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShiftManagerTest extends TestCase
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
        Livewire::test(ShiftManager::class)->assertStatus(200);
    }

    public function test_can_add_new_shift(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ShiftManager::class)
            ->set('nama_shift', 'Shift Siang')
            ->set('jam_masuk_mulai', '12:00')
            ->set('jam_masuk_selesai', '13:00')
            ->set('jam_pulang_mulai', '20:00')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('attendance_settings', ['nama_shift' => 'Shift Siang']);
    }

    public function test_save_validates_required_fields(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ShiftManager::class)
            ->set('nama_shift', '')
            ->call('save')
            ->assertHasErrors(['nama_shift' => 'required']);
    }

    public function test_can_toggle_shift_status(): void
    {
        $this->actingAs($this->admin);

        $shift = AttendanceSetting::create([
            'nama_shift' => 'Shift Test', 'jam_masuk_mulai' => '07:00',
            'jam_masuk_selesai' => '08:00', 'jam_pulang_mulai' => '16:00', 'status_aktif' => true,
        ]);

        Livewire::test(ShiftManager::class)->call('toggleStatus', $shift->id);

        $this->assertFalse($shift->fresh()->status_aktif);
    }
}
```

- [ ] **Step 3: Run tests — expect FAIL**

```bash
php artisan test tests/Feature/Livewire/ShiftManagerTest.php
```

Expected: Fail — component has no logic.

- [ ] **Step 4: Implement ShiftManager class**

Replace `app/Livewire/Settings/ShiftManager.php`:

```php
<?php

namespace App\Livewire\Settings;

use App\Models\AttendanceSetting;
use Livewire\Component;

class ShiftManager extends Component
{
    public string $nama_shift = '';
    public string $jam_masuk_mulai = '';
    public string $jam_masuk_selesai = '';
    public string $jam_pulang_mulai = '';
    public bool $showForm = false;

    protected $rules = [
        'nama_shift'        => 'required|string|max:100',
        'jam_masuk_mulai'   => 'required',
        'jam_masuk_selesai' => 'required',
        'jam_pulang_mulai'  => 'required',
    ];

    public function save(): void
    {
        $this->validate();

        AttendanceSetting::create([
            'nama_shift'        => $this->nama_shift,
            'jam_masuk_mulai'   => $this->jam_masuk_mulai,
            'jam_masuk_selesai' => $this->jam_masuk_selesai,
            'jam_pulang_mulai'  => $this->jam_pulang_mulai,
            'status_aktif'      => true,
        ]);

        $this->reset(['nama_shift', 'jam_masuk_mulai', 'jam_masuk_selesai', 'jam_pulang_mulai', 'showForm']);
        session()->flash('success', 'Shift berhasil ditambahkan.');
    }

    public function toggleStatus(int $id): void
    {
        $shift = AttendanceSetting::findOrFail($id);
        $shift->update(['status_aktif' => ! $shift->status_aktif]);
    }

    public function delete(int $id): void
    {
        AttendanceSetting::findOrFail($id)->delete();
    }

    public function render()
    {
        return view('livewire.settings.shift-manager', [
            'shifts' => AttendanceSetting::orderBy('nama_shift')->get(),
        ]);
    }
}
```

- [ ] **Step 5: Write the Blade view**

Replace `resources/views/livewire/settings/shift-manager.blade.php`:

```blade
<div>
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-lg font-semibold text-gray-800">Daftar Shift Kerja</h2>
        <button wire:click="$set('showForm', true)"
                class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
            + Tambah Shift
        </button>
    </div>

    @if($showForm)
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Tambah Shift Baru</h3>
        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Nama Shift</label>
                <input wire:model="nama_shift" type="text" placeholder="Contoh: Shift Pagi"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('nama_shift') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Jam Masuk Mulai</label>
                <input wire:model="jam_masuk_mulai" type="time"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('jam_masuk_mulai') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Batas Tepat Waktu</label>
                <input wire:model="jam_masuk_selesai" type="time"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('jam_masuk_selesai') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Jam Pulang Mulai</label>
                <input wire:model="jam_pulang_mulai" type="time"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('jam_pulang_mulai') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="flex gap-3 mt-4">
            <button wire:click="save" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">Simpan</button>
            <button wire:click="$set('showForm', false)" class="px-4 py-2 text-gray-600 text-sm rounded-lg hover:bg-gray-100">Batal</button>
        </div>
    </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Nama Shift</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Jam Masuk</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Batas Tepat Waktu</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Jam Pulang</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($shifts as $shift)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $shift->nama_shift }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ substr($shift->jam_masuk_mulai, 0, 5) }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ substr($shift->jam_masuk_selesai, 0, 5) }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ substr($shift->jam_pulang_mulai, 0, 5) }}</td>
                    <td class="px-4 py-3">
                        <button wire:click="toggleStatus({{ $shift->id }})"
                                class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $shift->status_aktif ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $shift->status_aktif ? 'Aktif' : 'Nonaktif' }}
                        </button>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <button wire:click="delete({{ $shift->id }})" wire:confirm="Hapus shift ini?"
                                class="text-red-400 hover:text-red-600 text-xs">Hapus</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-400 text-sm">Belum ada shift.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
```

- [ ] **Step 6: Run tests — expect PASS**

```bash
php artisan test tests/Feature/Livewire/ShiftManagerTest.php
```

Expected: 4 tests, 4 passed.

- [ ] **Step 7: Commit**

```bash
git add app/Livewire/Settings/ resources/views/livewire/settings/ tests/Feature/Livewire/ShiftManagerTest.php
git commit -m "feat: add ShiftManager component for shift CRUD"
```

---

## Task 10: ClockInOut Livewire Component (TDD)

**Files:** `app/Livewire/Attendance/ClockInOut.php`, `resources/views/livewire/attendance/clock-in-out.blade.php`, `tests/Feature/Livewire/ClockInOutTest.php`

- [ ] **Step 1: Generate component**

```bash
php artisan make:livewire Attendance/ClockInOut
```

- [ ] **Step 2: Write the failing tests**

Create `tests/Feature/Livewire/ClockInOutTest.php`:

```php
<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Attendance\ClockInOut;
use App\Models\AttendanceSetting;
use App\Models\Employee;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClockInOutTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'pegawai']);
        $this->employee = Employee::create([
            'user_id' => $this->user->id, 'nip' => '999',
            'nama_lengkap' => 'Test User', 'status_aktif' => true, 'tanggal_masuk' => today(),
        ]);

        AttendanceSetting::create([
            'nama_shift' => 'Shift Pagi', 'jam_masuk_mulai' => '07:00:00',
            'jam_masuk_selesai' => '08:00:00', 'jam_pulang_mulai' => '16:00:00', 'status_aktif' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_renders_clock_in_button_when_not_clocked_in(): void
    {
        $this->actingAs($this->user);
        Livewire::test(ClockInOut::class)->assertSee('Absen Masuk');
    }

    public function test_clock_in_creates_record(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(7, 30));
        $this->actingAs($this->user);

        Livewire::test(ClockInOut::class)
            ->set('lokasi', 'Kantor Pusat')
            ->call('clockIn')
            ->assertHasNoErrors()
            ->assertSee('Absen Pulang');

        $this->assertDatabaseHas('attendances', ['employee_id' => $this->employee->id]);
    }

    public function test_shows_clock_out_after_clock_in(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(7, 30));
        $this->actingAs($this->user);
        (new AttendanceService())->clockIn($this->employee, 'Kantor');

        Livewire::test(ClockInOut::class)
            ->assertSee('Absen Pulang')
            ->assertDontSee('Absen Masuk');
    }

    public function test_clock_in_requires_lokasi(): void
    {
        $this->actingAs($this->user);
        Livewire::test(ClockInOut::class)
            ->set('lokasi', '')
            ->call('clockIn')
            ->assertHasErrors(['lokasi' => 'required']);
    }
}
```

- [ ] **Step 3: Run tests — expect FAIL**

```bash
php artisan test tests/Feature/Livewire/ClockInOutTest.php
```

Expected: Fail — component has no logic.

- [ ] **Step 4: Implement ClockInOut class**

Replace `app/Livewire/Attendance/ClockInOut.php`:

```php
<?php

namespace App\Livewire\Attendance;

use App\Models\Attendance;
use App\Services\AttendanceService;
use Livewire\Component;

class ClockInOut extends Component
{
    public string $lokasi = '';
    public ?Attendance $todayAttendance = null;
    public ?string $errorMessage = null;

    protected $rules = ['lokasi' => 'required|string|max:255'];

    public function mount(AttendanceService $service): void
    {
        $employee = auth()->user()->employee;
        $this->todayAttendance = $employee ? $service->todayAttendance($employee) : null;
    }

    public function clockIn(AttendanceService $service): void
    {
        $this->validate();
        $this->errorMessage = null;

        try {
            $this->todayAttendance = $service->clockIn(auth()->user()->employee, $this->lokasi);
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

- [ ] **Step 5: Write the Blade view**

Replace `resources/views/livewire/attendance/clock-in-out.blade.php`:

```blade
<div class="bg-white rounded-xl border border-gray-200 p-6 max-w-md">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-semibold text-gray-800">Absensi Hari Ini</h2>
        <span class="text-sm text-gray-400"
              x-data="{ time: '' }"
              x-init="setInterval(() => { time = new Date().toLocaleTimeString('id-ID') }, 1000)"
              x-text="time"></span>
    </div>

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
</div>
```

- [ ] **Step 6: Run tests — expect PASS**

```bash
php artisan test tests/Feature/Livewire/ClockInOutTest.php
```

Expected: 4 tests, 4 passed.

- [ ] **Step 7: Commit**

```bash
git add app/Livewire/Attendance/ClockInOut.php resources/views/livewire/attendance/clock-in-out.blade.php tests/Feature/Livewire/ClockInOutTest.php
git commit -m "feat: add ClockInOut component with late detection and location input"
```

---

## Task 11: AttendanceHistory Component

**Files:** `app/Livewire/Attendance/AttendanceHistory.php`, `resources/views/livewire/attendance/attendance-history.blade.php`

- [ ] **Step 1: Generate component**

```bash
php artisan make:livewire Attendance/AttendanceHistory
```

- [ ] **Step 2: Implement component class**

Replace `app/Livewire/Attendance/AttendanceHistory.php`:

```php
<?php

namespace App\Livewire\Attendance;

use App\Models\Attendance;
use Livewire\Component;
use Livewire\WithPagination;

class AttendanceHistory extends Component
{
    use WithPagination;

    public function render()
    {
        $employeeId = auth()->user()->employee?->id ?? 0;

        return view('livewire.attendance.attendance-history', [
            'attendances' => Attendance::where('employee_id', $employeeId)
                ->orderBy('tanggal', 'desc')
                ->paginate(15),
        ]);
    }
}
```

- [ ] **Step 3: Write the Blade view**

Replace `resources/views/livewire/attendance/attendance-history.blade.php`:

```blade
<div>
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Jam Masuk</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Jam Pulang</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Keterangan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($attendances as $record)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $record->tanggal->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $record->jam_masuk ? substr($record->jam_masuk, 0, 5) : '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $record->jam_pulang ? substr($record->jam_pulang, 0, 5) : '—' }}</td>
                    <td class="px-4 py-3">
                        @php $badge = match($record->status_kehadiran) {
                            'hadir' => 'bg-green-100 text-green-700',
                            'izin'  => 'bg-blue-100 text-blue-700',
                            'sakit' => 'bg-yellow-100 text-yellow-700',
                            'alfa'  => 'bg-red-100 text-red-700',
                            default => 'bg-gray-100 text-gray-600',
                        }; @endphp
                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge }} capitalize">
                            {{ $record->status_kehadiran }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $record->keterangan ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-400 text-sm">Belum ada riwayat absensi.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($attendances->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">{{ $attendances->links() }}</div>
        @endif
    </div>
</div>
```

- [ ] **Step 4: Commit**

```bash
git add app/Livewire/Attendance/AttendanceHistory.php resources/views/livewire/attendance/attendance-history.blade.php
git commit -m "feat: add AttendanceHistory component for employee self-view"
```

---

## Task 12: AdminAttendanceTable Component (TDD)

**Files:** `app/Livewire/Attendance/AdminAttendanceTable.php`, `resources/views/livewire/attendance/admin-attendance-table.blade.php`, `tests/Feature/Livewire/AdminAttendanceTableTest.php`

- [ ] **Step 1: Generate component**

```bash
php artisan make:livewire Attendance/AdminAttendanceTable
```

- [ ] **Step 2: Write the failing tests**

Create `tests/Feature/Livewire/AdminAttendanceTableTest.php`:

```php
<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Attendance\AdminAttendanceTable;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAttendanceTableTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);

        $pegawai = User::factory()->create(['role' => 'pegawai']);
        $this->employee = Employee::create([
            'user_id' => $pegawai->id, 'nip' => '001',
            'nama_lengkap' => 'Pegawai Test', 'status_aktif' => true, 'tanggal_masuk' => today(),
        ]);
    }

    public function test_renders_for_admin(): void
    {
        $this->actingAs($this->admin);
        Livewire::test(AdminAttendanceTable::class)->assertStatus(200);
    }

    public function test_shows_todays_attendance_by_default(): void
    {
        $this->actingAs($this->admin);

        Attendance::create([
            'employee_id' => $this->employee->id, 'tanggal' => today(),
            'jam_masuk' => '07:30:00', 'status_kehadiran' => 'hadir',
        ]);

        Livewire::test(AdminAttendanceTable::class)->assertSee('Pegawai Test');
    }

    public function test_can_filter_by_status(): void
    {
        $this->actingAs($this->admin);

        Attendance::create([
            'employee_id' => $this->employee->id, 'tanggal' => today(),
            'jam_masuk' => '07:30:00', 'status_kehadiran' => 'izin',
        ]);

        Livewire::test(AdminAttendanceTable::class)
            ->set('filterStatus', 'hadir')
            ->assertDontSee('Pegawai Test');
    }
}
```

- [ ] **Step 3: Run tests — expect FAIL**

```bash
php artisan test tests/Feature/Livewire/AdminAttendanceTableTest.php
```

- [ ] **Step 4: Implement AdminAttendanceTable class**

Replace `app/Livewire/Attendance/AdminAttendanceTable.php`:

```php
<?php

namespace App\Livewire\Attendance;

use App\Models\Attendance;
use Livewire\Component;
use Livewire\WithPagination;

class AdminAttendanceTable extends Component
{
    use WithPagination;

    public string $filterTanggal = '';
    public string $filterStatus = '';

    public function mount(): void
    {
        $this->filterTanggal = today()->toDateString();
    }

    public function updatingFilterTanggal(): void { $this->resetPage(); }
    public function updatingFilterStatus(): void  { $this->resetPage(); }

    public function render()
    {
        $attendances = Attendance::with('employee')
            ->when($this->filterTanggal, fn ($q) => $q->whereDate('tanggal', $this->filterTanggal))
            ->when($this->filterStatus,  fn ($q) => $q->where('status_kehadiran', $this->filterStatus))
            ->orderBy('tanggal', 'desc')
            ->orderBy('jam_masuk')
            ->paginate(20);

        return view('livewire.attendance.admin-attendance-table', compact('attendances'));
    }
}
```

- [ ] **Step 5: Write the Blade view**

Replace `resources/views/livewire/attendance/admin-attendance-table.blade.php`:

```blade
<div>
    <div class="flex flex-wrap gap-4 mb-6">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal</label>
            <input wire:model.live="filterTanggal" type="date"
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Status Kehadiran</label>
            <select wire:model.live="filterStatus"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Semua Status</option>
                <option value="hadir">Hadir</option>
                <option value="izin">Izin</option>
                <option value="sakit">Sakit</option>
                <option value="alfa">Alfa</option>
            </select>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Nama Pegawai</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">NIP</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Jam Masuk</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Jam Pulang</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Keterangan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($attendances as $record)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $record->employee->nama_lengkap }}</td>
                    <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ $record->employee->nip }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $record->tanggal->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $record->jam_masuk ? substr($record->jam_masuk, 0, 5) : '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $record->jam_pulang ? substr($record->jam_pulang, 0, 5) : '—' }}</td>
                    <td class="px-4 py-3">
                        @php $badge = match($record->status_kehadiran) {
                            'hadir' => 'bg-green-100 text-green-700',
                            'izin'  => 'bg-blue-100 text-blue-700',
                            'sakit' => 'bg-yellow-100 text-yellow-700',
                            'alfa'  => 'bg-red-100 text-red-700',
                            default => 'bg-gray-100 text-gray-600',
                        }; @endphp
                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge }} capitalize">
                            {{ $record->status_kehadiran }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $record->keterangan ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">Tidak ada data untuk filter yang dipilih.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($attendances->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">{{ $attendances->links() }}</div>
        @endif
    </div>
</div>
```

- [ ] **Step 6: Run tests — expect PASS**

```bash
php artisan test tests/Feature/Livewire/AdminAttendanceTableTest.php
```

Expected: 3 tests, 3 passed.

- [ ] **Step 7: Commit**

```bash
git add app/Livewire/Attendance/AdminAttendanceTable.php resources/views/livewire/attendance/admin-attendance-table.blade.php tests/Feature/Livewire/AdminAttendanceTableTest.php
git commit -m "feat: add AdminAttendanceTable with date and status filters"
```

---

## Task 13: Final Verification

- [ ] **Step 1: Fresh migrate and seed**

```bash
php artisan migrate:fresh --seed
```

Expected: All migrations run, 6 users + 5 employees + 1 shift seeded.

- [ ] **Step 2: Run full test suite**

```bash
php artisan test
```

Expected: All tests pass. Record the total count.

- [ ] **Step 3: Build assets**

```bash
npm run build
```

Expected: No errors.

- [ ] **Step 4: Start dev server and smoke-test**

```bash
php artisan serve
```

Open `http://localhost:8000` and verify:

| Scenario | Expected |
|---|---|
| Login as `admin@puspa.test` / `password` | Redirected to `/dashboard`, sidebar shows Admin section |
| Admin visits `/admin/pengaturan-jam` | ShiftManager renders, can add/toggle shifts |
| Admin visits `/admin/absensi` | Monitor table shows today's records, filters work |
| Login as `siti@puspa.test` / `password` | Dashboard shows ClockInOut widget |
| Pegawai types location + clicks "Absen Masuk" | Record created, button changes to "Absen Pulang" |
| Pegawai visits `/absensi/riwayat` | Own attendance history shown |
| Admin tries `/absensi/clock-in` | 403 Forbidden |

- [ ] **Step 5: Final commit**

```bash
git add -A
git commit -m "feat: complete Attendance Module — clock-in/out, history, admin monitor, shift settings"
```
