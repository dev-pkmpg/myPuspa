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

        if ($status->assignments()->exists()) {
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
