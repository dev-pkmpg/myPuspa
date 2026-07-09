# Personnel Management System — Attendance Module Design

**Date:** 2026-07-09
**Project:** myPuspa (Sistem Kepegawaian)
**Scope:** Core infrastructure + Attendance Module (initial phase)
**Stack:** Laravel 13 · Livewire · Tailwind CSS 4 · Alpine.js (TALL)

---

## 1. Architecture Decision

**Pattern:** Conventional Laravel + Service Layer (Approach A)

Business logic lives in `app/Services/`. Livewire components call services; services call models. No module package overhead for now. Migration path to `nwidart/laravel-modules` exists if the codebase grows to warrant it.

---

## 2. Database Schema

### `users` table (extended via migration)
| Column | Type | Notes |
|---|---|---|
| role | enum(`admin`, `pegawai`) | default `pegawai` |

All other columns are standard Laravel users columns (id, name, email, password, timestamps).

### `employees` table
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| user_id | bigint FK | unique · references users.id |
| nip | varchar(20) | unique |
| nama_lengkap | varchar(255) | |
| status_aktif | boolean | default true |
| tanggal_masuk | date | |
| timestamps | | |

> `email` omitted — lives on `users` and accessed via relationship.

### `attendance_settings` table
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| nama_shift | varchar(100) | e.g. "Shift Pagi" |
| jam_masuk_mulai | time | earliest valid clock-in |
| jam_masuk_selesai | time | latest on-time clock-in (late threshold) |
| jam_pulang_mulai | time | earliest valid clock-out |
| status_aktif | boolean | default true |
| timestamps | | |

### `attendances` table
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| employee_id | bigint FK | references employees.id |
| tanggal | date | |
| jam_masuk | time | nullable |
| jam_pulang | time | nullable |
| status_kehadiran | enum | `hadir` · `izin` · `sakit` · `alfa` |
| lokasi_masuk | text | nullable · plain text / coordinates string |
| lokasi_pulang | text | nullable |
| keterangan | text | nullable · used for "Terlambat" note |
| timestamps | | |

**Unique constraint:** `(employee_id, tanggal)` — one attendance record per employee per day.

### Eloquent Relationships
- `User` hasOne `Employee`
- `Employee` belongsTo `User`
- `Employee` hasMany `Attendance`
- `Attendance` belongsTo `Employee`

---

## 3. Authentication & Role System

**Package:** Laravel Breeze (Livewire/Volt stack)

Public registration is disabled — admin creates employee accounts via the system.

### Middleware
`EnsureUserHasRole` middleware registered as `role` alias in `bootstrap/app.php`.

### Route Groups
```
/login                        public
/dashboard                    auth (any role)
/absensi/clock-in             auth · pegawai
/absensi/riwayat              auth · pegawai
/admin/absensi                auth · admin
/admin/pengaturan-jam         auth · admin
/admin/pegawai                auth · admin (placeholder, future)
```

---

## 4. Directory Structure

```
app/
  Http/
    Middleware/
      EnsureUserHasRole.php
  Livewire/
    Attendance/
      ClockInOut.php            employee dashboard clock widget
      AttendanceHistory.php     employee's own paginated records
      AdminAttendanceTable.php  admin monitor with date + status filters
    Settings/
      ShiftManager.php          CRUD for AttendanceSetting records
    Employee/
      EmployeeTable.php         placeholder for future Kepegawaian module
  Models/
    User.php                    + role column + employee() relation
    Employee.php
    Attendance.php
    AttendanceSetting.php
  Services/
    AttendanceService.php
    EmployeeService.php

resources/views/
  layouts/
    app.blade.php               authenticated layout with sidebar
    guest.blade.php             login layout
  livewire/
    attendance/
    settings/
  components/
    sidebar.blade.php
```

---

## 5. Service Layer — Business Logic

### AttendanceService

**`clockIn(Employee $employee, string $lokasi): Attendance`**
1. Abort with exception if an attendance record already exists for today.
2. Load the first active `AttendanceSetting` (`status_aktif = true`).
3. Compare `now()->toTimeString()` vs. `jam_masuk_selesai`:
   - ≤ threshold → `status_kehadiran = 'hadir'`
   - > threshold → `status_kehadiran = 'hadir'`, `keterangan = 'Terlambat'`
4. Create and return the `Attendance` record.

**`clockOut(Attendance $attendance, string $lokasi): Attendance`**
1. Assert `jam_masuk` is set and `jam_pulang` is null — throw otherwise.
2. Optionally assert `now()` ≥ `jam_pulang_mulai` (early clock-out guard).
3. Set `jam_pulang` and `lokasi_pulang`, save and return.

### EmployeeService

**`create(array $data): Employee`**
1. Wrap in `DB::transaction`.
2. Create `User` with hashed password, `role = 'pegawai'`.
3. Create `Employee` linked via `user_id`.
4. Return the `Employee`.

---

## 6. Livewire Components

| Component | Key Behaviour |
|---|---|
| `ClockInOut` | Shows current time (Alpine.js ticker). Disables Clock In if already checked in. Disables Clock Out if not yet checked in. Accepts plain-text `lokasi` input before submitting. |
| `AttendanceHistory` | Scoped to `auth()->user()->employee`. Paginated. |
| `AdminAttendanceTable` | Filter by `tanggal` (default: today) and `status_kehadiran`. All employees visible. |
| `ShiftManager` | Inline add/edit rows. Toggle `status_aktif` per shift. |

---

## 7. Sidebar Navigation

```
Dashboard
Absensi
  ├─ Clock In / Out        (pegawai only)
  └─ Riwayat Absensi
Admin
  ├─ Monitor Absensi       (admin only)
  └─ Pengaturan Jam Kerja
── coming soon ──
Kepegawaian                (placeholder, disabled)
Penggajian (Payroll)       (placeholder, disabled)
Penilaian Kinerja          (placeholder, disabled)
```

---

## 8. Packages to Install

| Package | Purpose |
|---|---|
| `laravel/breeze` | Auth scaffolding (Livewire/Volt stack) |
| `livewire/livewire` | Reactive UI components |
| `livewire/volt` | Single-file Livewire components (bundled with Breeze Livewire) |
| `alpinejs` | Bundled via npm with Breeze |
| `fakerphp/faker` | Already present — used for seeders |

Tailwind CSS 4 is already in `package.json`.

---

## 9. Seeders

- `RoleSeeder` — not needed (role is a column, not a table)
- `AttendanceSettingSeeder` — seeds one default shift (e.g. 07:00–08:00 masuk, 16:00 pulang)
- `UserSeeder` — creates one admin user + 5 dummy pegawai, each with a linked `Employee`

---

## 10. Out of Scope (This Phase)

- GPS / Geolocation capture (location is plain text input)
- Public employee self-registration
- Payroll, Performance, or other modules
- File/photo upload for attendance proof
- Notifications or email alerts
