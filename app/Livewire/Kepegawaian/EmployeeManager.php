<?php

namespace App\Livewire\Kepegawaian;

use App\Models\Employee;
use App\Models\Jabatan;
use App\Models\Klaster;
use App\Models\StatusPegawai;
use App\Services\EmployeeService;
use Livewire\Component;

class EmployeeManager extends Component
{
    public string $nama_lengkap = '';
    public string $email = '';
    public string $password = '';
    public string $nip = '';
    public string $nrk = '';
    public string $nik = '';
    public string $npwp = '';
    public string $nomor_bpjs_ketenagakerjaan = '';
    public string $nomor_bpjs_kesehatan = '';
    public string $id_sip = '';
    public string $id_str = '';
    public string $nomor_hp = '';
    public string $email_pribadi = '';
    public ?string $status_pernikahan = null;
    public string $tanggal_masuk = '';
    public ?int $jabatan_id = null;
    public ?int $status_pegawai_id = null;
    public ?int $klaster_id = null;
    public bool $status_aktif = true;
    public bool $showForm = false;
    public ?int $editingId = null;
    public ?int $editingUserId = null;
    public ?int $historyEmployeeId = null;

    public function rules(): array
    {
        $nipRule   = 'required|string|max:20|unique:employees,nip' . ($this->editingId ? ',' . $this->editingId : '');
        $nrkRule   = 'nullable|string|max:20|unique:employees,nrk' . ($this->editingId ? ',' . $this->editingId : '');
        $emailRule = 'required|email|unique:users,email' . ($this->editingUserId ? ',' . $this->editingUserId : '');
        $pwRule    = $this->editingId ? 'nullable|string|min:8' : 'required|string|min:8';

        return [
            'nama_lengkap'      => 'required|string|max:255',
            'email'             => $emailRule,
            'password'          => $pwRule,
            'nip'               => $nipRule,
            'nrk'               => $nrkRule,
            'tanggal_masuk'     => 'required|date',
            'jabatan_id'                  => 'nullable|exists:jabatans,id',
            'status_pegawai_id'           => 'nullable|exists:status_pegawais,id',
            'klaster_id'                  => 'nullable|exists:klasters,id',
            'nik'                         => 'nullable|string|max:16|unique:employees,nik' . ($this->editingId ? ',' . $this->editingId : ''),
            'npwp'                        => 'nullable|string|max:20',
            'nomor_bpjs_ketenagakerjaan'  => 'nullable|string|max:20',
            'nomor_bpjs_kesehatan'        => 'nullable|string|max:20',
            'id_sip'                      => 'nullable|string|max:100',
            'id_str'                      => 'nullable|string|max:100',
            'nomor_hp'                    => 'nullable|string|max:20',
            'email_pribadi'               => 'nullable|email|max:255',
            'status_pernikahan'           => 'nullable|in:belum_menikah,menikah,cerai_hidup,cerai_mati',
            'status_aktif'                => 'boolean',
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
            'nrk'               => $this->nrk ?: null,
            'tanggal_masuk'     => $this->tanggal_masuk,
            'jabatan_id'                 => $this->jabatan_id ?: null,
            'status_pegawai_id'          => $this->status_pegawai_id ?: null,
            'klaster_id'                 => $this->klaster_id ?: null,
            'nik'                        => $this->nik ?: null,
            'npwp'                       => $this->npwp ?: null,
            'nomor_bpjs_ketenagakerjaan' => $this->nomor_bpjs_ketenagakerjaan ?: null,
            'nomor_bpjs_kesehatan'       => $this->nomor_bpjs_kesehatan ?: null,
            'id_sip'                     => $this->id_sip ?: null,
            'id_str'                     => $this->id_str ?: null,
            'nomor_hp'                   => $this->nomor_hp ?: null,
            'email_pribadi'              => $this->email_pribadi ?: null,
            'status_pernikahan'          => $this->status_pernikahan ?: null,
            'status_aktif'               => $this->status_aktif,
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
        $employee = Employee::with(['user', 'currentAssignment'])->findOrFail($id);
        $this->editingId         = $id;
        $this->editingUserId     = $employee->user_id;
        $this->nama_lengkap      = $employee->nama_lengkap;
        $this->email             = $employee->user->email;
        $this->password          = '';
        $this->nip               = $employee->nip;
        $this->nrk               = $employee->nrk ?? '';
        $this->tanggal_masuk     = $employee->tanggal_masuk->format('Y-m-d');
        $this->jabatan_id                 = $employee->currentAssignment?->jabatan_id;
        $this->status_pegawai_id          = $employee->currentAssignment?->status_pegawai_id;
        $this->klaster_id                 = $employee->currentAssignment?->klaster_id;
        $this->nik                        = $employee->nik ?? '';
        $this->npwp                       = $employee->npwp ?? '';
        $this->nomor_bpjs_ketenagakerjaan = $employee->nomor_bpjs_ketenagakerjaan ?? '';
        $this->nomor_bpjs_kesehatan       = $employee->nomor_bpjs_kesehatan ?? '';
        $this->id_sip                     = $employee->id_sip ?? '';
        $this->id_str                     = $employee->id_str ?? '';
        $this->nomor_hp                   = $employee->nomor_hp ?? '';
        $this->email_pribadi              = $employee->email_pribadi ?? '';
        $this->status_pernikahan          = $employee->status_pernikahan;
        $this->status_aktif               = $employee->status_aktif;
        $this->showForm                   = true;
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
            'nama_lengkap', 'email', 'password', 'nip', 'nrk', 'tanggal_masuk',
            'jabatan_id', 'status_pegawai_id', 'klaster_id',
            'nik', 'npwp', 'nomor_bpjs_ketenagakerjaan', 'nomor_bpjs_kesehatan',
            'id_sip', 'id_str', 'nomor_hp', 'email_pribadi', 'status_pernikahan',
            'showForm', 'editingId', 'editingUserId',
        ]);
        $this->status_aktif = true;
    }

    public function showHistory(int $id): void
    {
        $this->historyEmployeeId = $this->historyEmployeeId === $id ? null : $id;
    }

    public function render()
    {
        $historyEmployee = $this->historyEmployeeId
            ? Employee::with(['assignments.jabatan', 'assignments.statusPegawai', 'assignments.klaster'])->find($this->historyEmployeeId)
            : null;

        return view('livewire.kepegawaian.employee-manager', [
            'employees'      => Employee::with(['user', 'currentAssignment.jabatan', 'currentAssignment.statusPegawai', 'currentAssignment.klaster'])->orderBy('nama_lengkap')->get(),
            'jabatans'       => Jabatan::where('aktif', true)->orderBy('nama_jabatan')->get(),
            'statusPegawais' => StatusPegawai::where('aktif', true)->orderBy('nama_status')->get(),
            'klasters'       => Klaster::aktif()->orderBy('nama_klaster')->get(),
            'historyEmployee' => $historyEmployee,
        ]);
    }
}
