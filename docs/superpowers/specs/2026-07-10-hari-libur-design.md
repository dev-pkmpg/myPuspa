# Desain Modul Hari Libur

**Tanggal:** 2026-07-10
**Status:** Disetujui

## Ringkasan

CRUD untuk menetapkan hari libur berdasarkan tanggal spesifik (bukan recurring mingguan). Data hari libur mempengaruhi sistem absensi — pegawai tidak bisa clock in pada hari libur. Dikelola hanya oleh admin, ditempatkan di section Pengaturan sidebar.

---

## Database

### Tabel Baru: `hari_liburs`

| Kolom      | Tipe           | Keterangan                          |
|------------|----------------|-------------------------------------|
| id         | bigint PK      |                                     |
| tanggal    | date, unique   | tidak bisa duplikat per tanggal     |
| nama       | string         | contoh: "Hari Raya Idul Fitri"      |
| keterangan | text nullable  |                                     |
| timestamps |                |                                     |

---

## Model

### Model Baru: `HariLibur`

- `fillable`: `tanggal`, `nama`, `keterangan`
- Cast `tanggal` sebagai `date`
- Scope `scopeOnDate($query, $date)` untuk cek cepat apakah tanggal tertentu adalah hari libur

---

## Livewire Component

### `Settings\HariLiburManager`

**View:** `livewire/settings/hari-libur-manager.blade.php`

Fitur:
- Tabel: Tanggal, Nama Hari Libur, Keterangan, Aksi
- Diurutkan berdasarkan `tanggal` ascending
- Form inline tambah: tanggal (date input), nama, keterangan
- Form inline edit per baris
- Hapus dengan konfirmasi SweetAlert2
- Guard: `abort_unless(auth()->user()?->isAdmin(), 403)` di semua method mutating

---

## Dampak ke Absensi

### Komponen `Attendance\ClockInOut` diupdate:

Saat render, cek apakah hari ini ada di tabel `hari_liburs`:

```php
$hariLibur = HariLibur::whereDate('tanggal', today())->first();
```

Jika ada:
- Variabel `$isHoliday` dan `$namaLibur` dikirim ke view
- Tombol Clock In dinonaktifkan (`disabled`, warna abu-abu)
- Tampil pesan: *"Hari ini adalah hari libur: [nama]. Tidak perlu absen."*

Jika tidak ada: perilaku clock-in tetap seperti semula.

---

## Routes

Ditambahkan di dalam group `prefix('admin')->name('admin.')->middleware('role:admin')` yang sudah ada:

```
GET /admin/pengaturan/hari-libur  →  admin.settings.holidays
```

**Page view baru:** `resources/views/pages/settings/holidays.blade.php`

---

## Sidebar

Section Admin diupdate, tambah link "Hari Libur" sejajar dengan "Pengaturan Jam Kerja":

```
Admin
  • Monitor Absensi
  • Pengaturan Jam Kerja
  • Hari Libur            ← baru
  ▼ Kepegawaian
      • Data Pegawai
      • Jabatan
      • Status Pegawai
```

Active state: `request()->routeIs('admin.settings.holidays')`

---

## Batasan

- Satu tanggal hanya bisa didaftarkan satu kali (unique constraint di DB + validasi Livewire).
- Tidak ada kategori hari libur — satu daftar flat.
- Hari libur mingguan (Sabtu/Minggu) tidak ditangani di sini.
- Dampak ke absensi hanya di level UI clock-in (blokir tombol) — tidak ada perubahan logika backend AttendanceService untuk saat ini.
