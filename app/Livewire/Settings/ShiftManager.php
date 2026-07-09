<?php

namespace App\Livewire\Settings;

use App\Models\AttendanceSetting;
use Livewire\Component;

class ShiftManager extends Component
{
    public string $nama_shift = '';
    public string $jam_masuk_mulai = '';
    public string $jam_masuk_selesai = '';
    public string $jam_pulang_mulai = '';
    public bool $showForm = false;

    protected $rules = [
        'nama_shift'        => 'required|string|max:100',
        'jam_masuk_mulai'   => 'required',
        'jam_masuk_selesai' => 'required',
        'jam_pulang_mulai'  => 'required',
    ];

    public function save(): void
    {
        $this->validate();

        AttendanceSetting::create([
            'nama_shift'        => $this->nama_shift,
            'jam_masuk_mulai'   => $this->jam_masuk_mulai,
            'jam_masuk_selesai' => $this->jam_masuk_selesai,
            'jam_pulang_mulai'  => $this->jam_pulang_mulai,
            'status_aktif'      => true,
        ]);

        $this->reset(['nama_shift', 'jam_masuk_mulai', 'jam_masuk_selesai', 'jam_pulang_mulai', 'showForm']);
        session()->flash('success', 'Shift berhasil ditambahkan.');
    }

    public function toggleStatus(int $id): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $shift = AttendanceSetting::findOrFail($id);
        $shift->update(['status_aktif' => ! $shift->status_aktif]);
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        AttendanceSetting::findOrFail($id)->delete();
    }

    public function render()
    {
        return view('livewire.settings.shift-manager', [
            'shifts' => AttendanceSetting::orderBy('nama_shift')->get(),
        ]);
    }
}
