<?php

namespace App\Livewire\Settings;

use App\Models\HariLibur;
use Livewire\Component;

class HariLiburManager extends Component
{
    public string $tanggal = '';
    public string $nama = '';
    public string $keterangan = '';
    public bool $showForm = false;
    public ?int $editingId = null;

    public function rules(): array
    {
        $tanggalRule = 'required|date|unique:hari_liburs,tanggal' . ($this->editingId ? ',' . $this->editingId : '');

        return [
            'tanggal'    => $tanggalRule,
            'nama'       => 'required|string|max:255',
            'keterangan' => 'nullable|string',
        ];
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $this->validate();

        $payload = [
            'tanggal'    => $this->tanggal,
            'nama'       => $this->nama,
            'keterangan' => $this->keterangan ?: null,
        ];

        if ($this->editingId) {
            HariLibur::findOrFail($this->editingId)->update($payload);
            session()->flash('success', 'Hari libur berhasil diperbarui.');
        } else {
            HariLibur::create($payload);
            session()->flash('success', 'Hari libur berhasil ditambahkan.');
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $libur = HariLibur::findOrFail($id);
        $this->editingId   = $id;
        $this->tanggal     = $libur->tanggal->format('Y-m-d');
        $this->nama        = $libur->nama;
        $this->keterangan  = $libur->keterangan ?? '';
        $this->showForm    = true;
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        HariLibur::findOrFail($id)->delete();
        session()->flash('success', 'Hari libur berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset(['tanggal', 'nama', 'keterangan', 'showForm', 'editingId']);
    }

    public function render()
    {
        return view('livewire.settings.hari-libur-manager', [
            'hariLiburs' => HariLibur::orderBy('tanggal')->get(),
        ]);
    }
}
