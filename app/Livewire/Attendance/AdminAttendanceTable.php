<?php

namespace App\Livewire\Attendance;

use App\Models\Attendance;
use Livewire\Component;
use Livewire\WithPagination;

class AdminAttendanceTable extends Component
{
    use WithPagination;

    public string $filterTanggal = '';
    public string $filterStatus = '';

    public function mount(): void
    {
        $this->filterTanggal = today()->toDateString();
    }

    public function updatingFilterTanggal(): void { $this->resetPage(); }
    public function updatingFilterStatus(): void  { $this->resetPage(); }

    public function render()
    {
        $attendances = Attendance::with('employee')
            ->when($this->filterTanggal, fn ($q) => $q->whereDate('tanggal', $this->filterTanggal))
            ->when($this->filterStatus,  fn ($q) => $q->where('status_kehadiran', $this->filterStatus))
            ->orderBy('tanggal', 'desc')
            ->orderBy('jam_masuk')
            ->paginate(20);

        return view('livewire.attendance.admin-attendance-table', compact('attendances'));
    }
}
