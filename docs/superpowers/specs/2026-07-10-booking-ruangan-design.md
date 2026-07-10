# Booking Ruangan — Design Spec

**Date:** 2026-07-10
**Status:** Approved

---

## Goal

Pegawai dapat melakukan booking ruangan secara mandiri. Booking memerlukan persetujuan dari manager ruangan. Admin mengelola data ruangan. Sistem menolak otomatis jika ada konflik jadwal.

---

## Roles

| Role | Kemampuan |
|---|---|
| `admin` | CRUD data ruangan |
| `pegawai` | Buat booking, lihat riwayat booking sendiri, cancel booking pending |
| `manager` | Sama dengan pegawai + approve/reject semua booking |

Role `manager` ditambahkan ke enum kolom `role` di tabel `users` (sejajar dengan `admin` dan `pegawai` yang sudah ada).

---

## Database

### Tabel `ruangans`

| Kolom | Tipe | Constraint |
|---|---|---|
| id | bigint | PK |
| nama | string(255) | not null |
| kapasitas | integer | not null |
| lokasi | string(255) | nullable |
| aktif | boolean | default true |
| created_at / updated_at | timestamps | |

### Tabel `booking_ruangans`

| Kolom | Tipe | Constraint |
|---|---|---|
| id | bigint | PK |
| ruangan_id | FK → ruangans | cascade delete |
| user_id | FK → users | cascade delete |
| tanggal | date | not null |
| jam_mulai | time | not null |
| jam_selesai | time | not null |
| keperluan | string(255) | not null |
| status | enum(pending, approved, rejected) | default pending |
| catatan_manager | string(255) | nullable |
| created_at / updated_at | timestamps | |

**Conflict rule:** Tidak boleh ada dua booking berstatus `pending` atau `approved` pada ruangan + tanggal yang sama dengan jam yang tumpang tindih. Pengecekan dilakukan di layer aplikasi saat `save()`.

Konflik terjadi jika: `jam_mulai_baru < jam_selesai_existing AND jam_selesai_baru > jam_mulai_existing`.

---

## Models

**`Ruangan`**
- fillable: `[nama, kapasitas, lokasi, aktif]`
- casts: `aktif → bool`
- hasMany: `BookingRuangan`
- scope: `aktif()` → filter `aktif = true`

**`BookingRuangan`**
- fillable: `[ruangan_id, user_id, tanggal, jam_mulai, jam_selesai, keperluan, status, catatan_manager]`
- casts: `tanggal → date:Y-m-d`
- belongsTo: `Ruangan`, `User`
- scope: `pending()`, `bentrok(ruangan_id, tanggal, jam_mulai, jam_selesai)` — cek overlap kecuali booking dengan id tertentu

---

## Livewire Components

### `RuanganManager` (admin only)

- Properties: `nama`, `kapasitas`, `lokasi`, `aktif`, `showForm`, `editingId`
- Methods: `save()`, `edit(id)`, `toggleAktif(id)`, `delete(id)` — delete guard: cek `bookings()->whereIn('status', ['pending','approved'])->exists()`
- View: tabel + inline form, SweetAlert2 delete confirm
- Route: `GET /ruangan → admin.ruangan.index`

### `BookingForm` (pegawai + manager)

- Properties: `ruangan_id`, `tanggal`, `jam_mulai`, `jam_selesai`, `keperluan`
- Methods: `save()` — validasi konflik jadwal sebelum insert
- Conflict check: query `booking_ruangans` dengan status `pending`/`approved`, ruangan + tanggal sama, jam overlap
- Setelah save: redirect ke riwayat dengan flash success
- Route: `GET /booking → booking.form`

### `BookingHistory` (pegawai + manager)

- Menampilkan semua booking milik `auth()->user()`
- Kolom: Ruangan, Tanggal, Jam Mulai–Selesai, Keperluan, Status (badge), Catatan Manager, Aksi
- Method: `cancel(id)` — hanya bisa cancel status `pending`, konfirmasi SweetAlert2
- Route: `GET /booking/riwayat → booking.history`

### `BookingApproval` (manager only)

- Menampilkan semua booking status `pending`, diurutkan tanggal ASC
- Kolom: Pemohon, Ruangan, Tanggal, Jam, Keperluan, Aksi
- Method: `approve(id)` — ubah status ke `approved`
- Method: `reject(id, catatan)` — ubah status ke `rejected`, simpan catatan_manager
- Reject menggunakan SweetAlert2 dengan input teks untuk catatan
- Route: `GET /manager/booking → manager.booking.index`

---

## Routes

```php
// Semua authenticated user
Route::get('/booking', fn () => view('pages.booking.form'))->name('booking.form');
Route::get('/booking/riwayat', fn () => view('pages.booking.history'))->name('booking.history');

// Admin only
Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
    // ... existing routes ...
    Route::get('/ruangan', fn () => view('pages.admin.ruangan'))->name('ruangan.index');
});

// Manager only
Route::prefix('manager')->name('manager.')->middleware('role:manager')->group(function () {
    Route::get('/booking', fn () => view('pages.manager.booking'))->name('booking.index');
});
```

---

## Sidebar

**Section "Ruangan"** — tampil untuk semua role (pegawai, manager, admin):
- Booking Ruangan → `/booking`
- Riwayat Booking → `/booking/riwayat`

**Section "Admin"** — hanya admin:
- Kelola Ruangan → `/ruangan` (tambahan dari yang sudah ada)

**Section "Manager"** — hanya manager:
- Approval Booking → `/manager/booking`

---

## Middleware

Tambahkan `manager` sebagai nilai yang diterima `role` middleware yang sudah ada. Pastikan `RoleMiddleware` menerima `role:manager`.

---

## Testing

Setiap komponen ditest dengan `Livewire::test()` + `RefreshDatabase`:

- **RuanganManagerTest**: renders, add, validate, edit, toggle aktif, cannot delete jika ada booking aktif, can delete jika tidak ada
- **BookingFormTest**: renders, can book, validate required, conflict check (booking ditolak jika jam bentrok), booking berhasil jika tidak ada konflik
- **BookingHistoryTest**: renders, shows own bookings only, can cancel pending, cannot cancel approved
- **BookingApprovalTest**: renders (as manager), approve changes status, reject saves catatan, non-manager diblokir 403
