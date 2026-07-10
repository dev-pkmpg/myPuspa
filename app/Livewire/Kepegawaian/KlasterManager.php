<?php

namespace App\Livewire\Kepegawaian;

use App\Models\Klaster;
use Livewire\Component;

class KlasterManager extends Component
{
    public string $nama_klaster = '';
    public string $keterangan = '';
    public bool $aktif = true;
    public bool $showForm = false;
    public ?int $editingId = null;

    protected function rules(): array
    {
        $namaRule = 'required|string|max:100|unique:klasters,nama_klaster' . ($this->editingId ? ',' . $this->editingId : '');

        return [
            'nama_klaster' => $namaRule,
            'keterangan'   => 'nullable|string|max:255',
            'aktif'        => 'boolean',
        ];
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $this->validate();

        if ($this->editingId) {
            Klaster::findOrFail($this->editingId)->update([
                'nama_klaster' => $this->nama_klaster,
                'keterangan'   => $this->keterangan ?: null,
                'aktif'        => $this->aktif,
            ]);
            session()->flash('success', 'Klaster berhasil diperbarui.');
        } else {
            Klaster::create([
                'nama_klaster' => $this->nama_klaster,
                'keterangan'   => $this->keterangan ?: null,
                'aktif'        => $this->aktif,
            ]);
            session()->flash('success', 'Klaster berhasil ditambahkan.');
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $klaster = Klaster::findOrFail($id);
        $this->editingId    = $id;
        $this->nama_klaster = $klaster->nama_klaster;
        $this->keterangan   = $klaster->keterangan ?? '';
        $this->aktif        = $klaster->aktif;
        $this->showForm     = true;
    }

    public function toggleAktif(int $id): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $klaster = Klaster::findOrFail($id);
        $klaster->update(['aktif' => ! $klaster->aktif]);
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $klaster = Klaster::findOrFail($id);

        if ($klaster->assignments()->exists()) {
            session()->flash('error', 'Klaster tidak dapat dihapus karena masih digunakan pegawai.');
            return;
        }

        $klaster->delete();
        session()->flash('success', 'Klaster berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset(['nama_klaster', 'keterangan', 'showForm', 'editingId']);
        $this->aktif = true;
    }

    public function render()
    {
        return view('livewire.kepegawaian.klaster-manager', [
            'klasters' => Klaster::orderBy('nama_klaster')->get(),
        ]);
    }
}
