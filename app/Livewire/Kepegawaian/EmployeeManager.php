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
    public string $nrk = '';
    public string $tanggal_masuk = '';
    public ?int $jabatan_id = null;
    public ?int $status_pegawai_id = null;
    public ?string $klaster = null;
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
            'nrk'               => $this->nrk ?: null,
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
        $employee = Employee::with(['user', 'currentAssignment'])->findOrFail($id);
        $this->editingId         = $id;
        $this->editingUserId     = $employee->user_id;
        $this->nama_lengkap      = $employee->nama_lengkap;
        $this->email             = $employee->user->email;
        $this->password          = '';
        $this->nip               = $employee->nip;
        $this->nrk               = $employee->nrk ?? '';
        $this->tanggal_masuk     = $employee->tanggal_masuk->format('Y-m-d');
        $this->jabatan_id        = $employee->currentAssignment?->jabatan_id;
        $this->status_pegawai_id = $employee->currentAssignment?->status_pegawai_id;
        $this->klaster           = $employee->currentAssignment?->klaster;
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
            'nama_lengkap', 'email', 'password', 'nip', 'nrk', 'tanggal_masuk',
            'jabatan_id', 'status_pegawai_id', 'klaster',
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
            ? Employee::with(['assignments.jabatan', 'assignments.statusPegawai'])->find($this->historyEmployeeId)
            : null;

        return view('livewire.kepegawaian.employee-manager', [
            'employees'       => Employee::with(['user', 'currentAssignment.jabatan', 'currentAssignment.statusPegawai'])->orderBy('nama_lengkap')->get(),
            'jabatans'        => Jabatan::where('aktif', true)->orderBy('nama_jabatan')->get(),
            'statusPegawais'  => StatusPegawai::where('aktif', true)->orderBy('nama_status')->get(),
            'klasterOptions'  => self::KLASTER_OPTIONS,
            'historyEmployee' => $historyEmployee,
        ]);
    }
}
