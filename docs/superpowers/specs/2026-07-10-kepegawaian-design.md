# Desain Modul Kepegawaian

**Tanggal:** 2026-07-10
**Status:** Disetujui

## Ringkasan

Mengaktifkan menu Kepegawaian di sidebar (saat ini "Segera Hadir") dengan tiga halaman: Data Pegawai, Jabatan, dan Status Pegawai. Seluruh fitur hanya bisa diakses oleh admin.

---

## Database

### Tabel Baru: `jabatans`

| Kolom         | Tipe              | Keterangan                  |
|---------------|-------------------|-----------------------------|
| id            | bigint PK         |                             |
| nama_jabatan  | string            | required                    |
| keterangan    | text nullable     |                             |
| aktif         | boolean default true |                          |
| timestamps    |                   |                             |

### Tabel Baru: `status_pegawais`

| Kolom         | Tipe              | Keterangan                  |
|---------------|-------------------|-----------------------------|
| id            | bigint PK         |                             |
| nama_status   | string            | required, contoh: PNS, PPPK, Honorer |
| keterangan    | text nullable     |                             |
| aktif         | boolean default true |                          |
| timestamps    |                   |                             |

### Perubahan Tabel `employees`

Tambah kolom:

| Kolom              | Tipe                          | Keterangan        |
|--------------------|-------------------------------|-------------------|
| jabatan_id         | FK → jabatans (nullable)      | nullable          |
| status_pegawai_id  | FK → status_pegawais (nullable) | nullable        |
| klaster            | enum, nullable                | nilai: `klaster_1`, `klaster_2`, `klaster_3`, `klaster_4`, `lintas_klaster` |

---

## Model

### Model Baru

- **`Jabatan`** — `fillable`: `nama_jabatan`, `keterangan`, `aktif`. Relasi: `hasMany Employee`.
- **`StatusPegawai`** — `fillable`: `nama_status`, `keterangan`, `aktif`. Relasi: `hasMany Employee`.

### Model Diupdate: `Employee`

- Tambah `jabatan_id`, `status_pegawai_id`, `klaster` ke `$fillable`
- Tambah cast `klaster` sebagai string
- Tambah relasi `belongsTo Jabatan` dan `belongsTo StatusPegawai`

### Service Diupdate: `EmployeeService`

Tambah method `update(Employee $employee, array $data): Employee`:
- Update data user (name, email, password opsional — hanya jika diisi)
- Update data employee (nip, nama_lengkap, jabatan_id, status_pegawai_id, klaster, status_aktif, tanggal_masuk)
- Dibungkus `DB::transaction`

---

## Livewire Components

### `Kepegawaian\EmployeeManager`

**View:** `livewire/kepegawaian/employee-manager.blade.php`

Fitur:
- Tabel pegawai: NIP, Nama, Jabatan, Status Pegawai, Klaster, Status Aktif, Aksi
- Form inline tambah pegawai: nama, email, password, NIP, tanggal masuk, jabatan (dropdown dari jabatan aktif), status pegawai (dropdown dari status aktif), klaster (dropdown enum), status_aktif
- Form inline edit pegawai: sama, password opsional (kosong = tidak diubah)
- Toggle `status_aktif` langsung dari tabel
- Hapus dengan konfirmasi SweetAlert2
- Guard: `abort_unless(auth()->user()?->isAdmin(), 403)` di semua method mutating

### `Kepegawaian\JabatanManager`

**View:** `livewire/kepegawaian/jabatan-manager.blade.php`

Fitur:
- Tabel: Nama Jabatan, Keterangan, Status, Aksi
- Form inline tambah & edit jabatan
- Toggle `aktif`
- Hapus dengan SweetAlert2 (cek apakah jabatan masih dipakai pegawai sebelum hapus)
- Guard admin di semua method mutating

### `Kepegawaian\StatusPegawaiManager`

**View:** `livewire/kepegawaian/status-pegawai-manager.blade.php`

Fitur identik dengan JabatanManager, untuk tabel `status_pegawais`.

---

## Routes

Ditambahkan di dalam group `prefix('admin')->name('admin.')->middleware('role:admin')` yang sudah ada:

```
GET /admin/kepegawaian                  → admin.kepegawaian.employees
GET /admin/kepegawaian/jabatan          → admin.kepegawaian.jabatan
GET /admin/kepegawaian/status-pegawai   → admin.kepegawaian.status-pegawai
```

---

## Sidebar

Section Admin diupdate:
- Hapus "Kepegawaian" dari daftar "Segera Hadir"
- Tambah submenu collapsible **Kepegawaian** di bawah section Admin dengan 3 link:
  - Data Pegawai → `admin.kepegawaian.employees`
  - Jabatan → `admin.kepegawaian.jabatan`
  - Status Pegawai → `admin.kepegawaian.status-pegawai`
- Submenu terbuka otomatis jika route aktif cocok dengan `admin.kepegawaian.*`

---

## Halaman Blade (page views)

Tiga file blade baru mengikuti pola `pages/settings/shifts.blade.php`:
- `resources/views/pages/kepegawaian/employees.blade.php`
- `resources/views/pages/kepegawaian/jabatan.blade.php`
- `resources/views/pages/kepegawaian/status-pegawai.blade.php`

Masing-masing berisi header halaman dan memanggil komponen Livewire yang sesuai.

---

## Batasan

- Klaster bukan tabel master — nilainya fixed enum (tidak ada CRUD klaster).
- Password pegawai wajib saat tambah, opsional saat edit.
- Jabatan dan Status Pegawai tidak bisa dihapus jika masih digunakan pegawai (akan tampil pesan error).
- Semua halaman kepegawaian hanya untuk role `admin`.
