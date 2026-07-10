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

        if ($jabatan->assignments()->exists()) {
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
