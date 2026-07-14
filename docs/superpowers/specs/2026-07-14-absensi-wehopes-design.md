# Design: Halaman Absensi Wehopes

**Tanggal:** 2026-07-14
**Status:** Approved

## Ringkasan

Mengganti halaman `ZktecoAbsensi` dengan halaman **Absensi** baru yang membaca dari database `wehopes` (tabel `tb_absen`) menggunakan koneksi `attendance` yang sudah dikonfigurasi di `.env`.

## Perubahan yang Dilakukan

### Hapus
- `app/Livewire/Absensi/ZktecoAbsensi.php`
- `resources/views/livewire/absensi/zkteco-absensi.blade.php`
- Route `/admin/absensi/mesin` (admin only)
- Page view `resources/views/pages/attendance/zkteco.blade.php` (jika ada)

### Buat Baru
- `app/Livewire/Absensi/Absensi.php` — Livewire component
- `resources/views/livewire/absensi/absensi.blade.php` — Template
- Route `/absensi/mesin` (semua user terauth, nama: `attendance.mesin`)
- Page wrapper (opsional, bisa inline)

## Sumber Data

| Item | Detail |
|------|--------|
| Koneksi DB | `attendance` (env: `ATTENDANCE_DB_*`) |
| Host | `10.50.176.200` |
| Database | `wehopes` |
| Tabel | `tb_absen` |
| Model | `App\Models\TbAbsen` |

### Kolom Tabel `tb_absen`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| `id` | int | Primary key |
| `pin` | int | NRK/PIN karyawan (linked ke `users.nrk`) |
| `date_time` | varchar | Waktu absen (`2025-03-01 06:02:48`) |
| `status` | int | `0` = Masuk, `1` = Keluar |
| `id_machine` | varchar | ID mesin fingerprint |
| `ver` | int | Versi record |

## Logika Akses

- **Admin** (`role:admin`): Melihat semua data dari semua NRK. Input "Cari NRK" ditampilkan.
- **Non-admin (pegawai, manager)**: Hanya melihat data absensi berdasarkan `nrk` milik sendiri (`auth()->user()->nrk`). Input cari NRK disembunyikan.

## Komponen Livewire

### State
```php
string $tanggal_dari   // default: awal bulan ini
string $tanggal_sampai // default: hari ini
string $search         // cari NRK (hanya admin)
bool $connected        // status koneksi DB attendance
string $connectionError
```

### Filter Query
- Ambil data dari `tb_absen` dengan `whereBetween('date_time', [...])`
- Jika admin dan `$search` diisi: `where('pin', 'like', '%search%')`
- Jika bukan admin: `where('pin', auth()->user()->nrk)` — wajib, tidak bisa diubah
- Order: `orderByDesc('date_time')`
- Pagination: 50 baris

### Lookup Nama
- Ambil unique `pin` dari hasil query
- Lookup ke `users` (DB utama) + eager load `employee:user_id,nama_lengkap`
- Map: `pin → nama_lengkap`

## Tampilan

### Kolom Tabel
| # | Kolom | Keterangan |
|---|-------|-----------|
| 1 | NRK | `pin` — hanya tampil jika admin |
| 2 | Nama Pegawai | dari lookup `users.employee.nama_lengkap` |
| 3 | Tanggal & Jam | `date_time` diformat `d/m/Y H:i:s` |
| 4 | Status | Badge: `0` = Masuk (hijau), `1` = Keluar (oranye) |
| 5 | Mesin | `id_machine` |

### Error State
Banner merah jika koneksi `attendance` DB gagal, tampilkan pesan error dan hint konfigurasi `.env`.

## Route

```php
// Semua user terauth (bukan hanya admin)
Route::get('/absensi/mesin', fn () => view('pages.attendance.mesin'))->name('attendance.mesin');
```
