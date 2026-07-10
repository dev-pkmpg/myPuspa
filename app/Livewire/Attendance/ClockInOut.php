<?php

namespace App\Livewire\Attendance;

use App\Models\Attendance;
use App\Models\HariLibur;
use App\Services\AttendanceService;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ClockInOut extends Component
{
    public string $lokasi = '';
    #[Locked]
    public ?Attendance $todayAttendance = null;
    public ?string $errorMessage = null;
    public ?string $hariLiburNama = null;

    protected $rules = ['lokasi' => 'required|string|max:255'];

    public function mount(AttendanceService $service): void
    {
        $employee = auth()->user()->employee;
        $this->todayAttendance = $employee ? $service->todayAttendance($employee) : null;

        $hariLibur = HariLibur::onDate(today())->first();
        $this->hariLiburNama = $hariLibur?->nama;
    }

    public function clockIn(AttendanceService $service): void
    {
        $this->validate();
        $this->errorMessage = null;

        $employee = auth()->user()->employee;
        if (! $employee) {
            $this->errorMessage = 'Data pegawai tidak ditemukan.';
            return;
        }

        try {
            $this->todayAttendance = $service->clockIn($employee, $this->lokasi);
            $this->lokasi = '';
        } catch (\RuntimeException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function clockOut(AttendanceService $service): void
    {
        $this->validate();
        $this->errorMessage = null;

        try {
            $this->todayAttendance = $service->clockOut($this->todayAttendance, $this->lokasi);
            $this->lokasi = '';
        } catch (\RuntimeException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.attendance.clock-in-out');
    }
}
