<?php

namespace App\Livewire\Kepegawaian;

use App\Models\Lokasi;
use Livewire\Component;

class LokasiManager extends Component
{
    public string $nama_lokasi = '';
    public string $keterangan = '';
    public bool $aktif = true;
    public bool $showForm = false;
    public ?int $editingId = null;

    protected function rules(): array
    {
        $namaRule = 'required|string|max:100|unique:lokasis,nama_lokasi' . ($this->editingId ? ',' . $this->editingId : '');

        return [
            'nama_lokasi' => $namaRule,
            'keterangan'  => 'nullable|string|max:255',
            'aktif'       => 'boolean',
        ];
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $this->validate();

        if ($this->editingId) {
            Lokasi::findOrFail($this->editingId)->update([
                'nama_lokasi' => $this->nama_lokasi,
                'keterangan'  => $this->keterangan ?: null,
                'aktif'       => $this->aktif,
            ]);
            session()->flash('success', 'Lokasi berhasil diperbarui.');
        } else {
            Lokasi::create([
                'nama_lokasi' => $this->nama_lokasi,
                'keterangan'  => $this->keterangan ?: null,
                'aktif'       => $this->aktif,
            ]);
            session()->flash('success', 'Lokasi berhasil ditambahkan.');
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $lokasi = Lokasi::findOrFail($id);
        $this->editingId   = $id;
        $this->nama_lokasi = $lokasi->nama_lokasi;
        $this->keterangan  = $lokasi->keterangan ?? '';
        $this->aktif       = $lokasi->aktif;
        $this->showForm    = true;
    }

    public function toggleAktif(int $id): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $lokasi = Lokasi::findOrFail($id);
        $lokasi->update(['aktif' => ! $lokasi->aktif]);
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $lokasi = Lokasi::findOrFail($id);

        if ($lokasi->assignments()->exists()) {
            session()->flash('error', 'Lokasi tidak dapat dihapus karena masih digunakan pegawai.');
            return;
        }

        $lokasi->delete();
        session()->flash('success', 'Lokasi berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset(['nama_lokasi', 'keterangan', 'showForm', 'editingId']);
        $this->aktif = true;
    }

    public function render()
    {
        return view('livewire.kepegawaian.lokasi-manager', [
            'lokasis' => Lokasi::orderBy('nama_lokasi')->get(),
        ]);
    }
}
