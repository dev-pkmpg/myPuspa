<?php

namespace App\Livewire\Ruangan;

use App\Models\Ruangan;
use Livewire\Component;

class RuanganManager extends Component
{
    public string $nama = '';
    public string $kapasitas = '';
    public string $lokasi = '';
    public bool $aktif = true;
    public bool $showForm = false;
    public ?int $editingId = null;

    protected $rules = [
        'nama'      => 'required|string|max:255',
        'kapasitas' => 'required|integer|min:1',
        'lokasi'    => 'nullable|string|max:255',
        'aktif'     => 'boolean',
    ];

    public function save(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $this->validate();

        $payload = [
            'nama'      => $this->nama,
            'kapasitas' => (int) $this->kapasitas,
            'lokasi'    => $this->lokasi ?: null,
            'aktif'     => $this->aktif,
        ];

        if ($this->editingId) {
            Ruangan::findOrFail($this->editingId)->update($payload);
            session()->flash('success', 'Ruangan berhasil diperbarui.');
        } else {
            Ruangan::create($payload);
            session()->flash('success', 'Ruangan berhasil ditambahkan.');
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $ruangan = Ruangan::findOrFail($id);
        $this->editingId = $id;
        $this->nama      = $ruangan->nama;
        $this->kapasitas = (string) $ruangan->kapasitas;
        $this->lokasi    = $ruangan->lokasi ?? '';
        $this->aktif     = $ruangan->aktif;
        $this->showForm  = true;
    }

    public function toggleAktif(int $id): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $ruangan = Ruangan::findOrFail($id);
        $ruangan->update(['aktif' => ! $ruangan->aktif]);
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $ruangan = Ruangan::findOrFail($id);

        if ($ruangan->bookings()->whereIn('status', ['pending', 'approved'])->exists()) {
            session()->flash('error', 'Ruangan tidak dapat dihapus karena masih ada booking aktif.');
            return;
        }

        $ruangan->delete();
        session()->flash('success', 'Ruangan berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset(['nama', 'kapasitas', 'lokasi', 'showForm', 'editingId']);
        $this->aktif = true;
    }

    public function render()
    {
        return view('livewire.ruangan.ruangan-manager', [
            'ruangans' => Ruangan::orderBy('nama')->get(),
        ]);
    }
}
