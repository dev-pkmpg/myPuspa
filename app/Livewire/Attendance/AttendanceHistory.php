<?php

namespace App\Livewire\Attendance;

use App\Models\Attendance;
use Livewire\Component;
use Livewire\WithPagination;

class AttendanceHistory extends Component
{
    use WithPagination;

    public function render()
    {
        $employeeId = auth()->user()->employee?->id ?? 0;

        return view('livewire.attendance.attendance-history', [
            'attendances' => Attendance::where('employee_id', $employeeId)
                ->orderBy('tanggal', 'desc')
                ->paginate(15),
        ]);
    }
}
