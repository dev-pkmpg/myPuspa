<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Employee;
use Carbon\Carbon;

class AttendanceService
{
    public function clockIn(Employee $employee, string $lokasi): Attendance
    {
        if (Attendance::where('employee_id', $employee->id)->whereDate('tanggal', today())->exists()) {
            throw new \RuntimeException('Sudah melakukan absen masuk hari ini.');
        }

        $setting = AttendanceSetting::where('status_aktif', true)->firstOrFail();
        $now = Carbon::now();
        $keterangan = $now->toTimeString() > $setting->jam_masuk_selesai ? 'Terlambat' : null;

        return Attendance::create([
            'employee_id'      => $employee->id,
            'tanggal'          => today(),
            'jam_masuk'        => $now->toTimeString(),
            'status_kehadiran' => 'hadir',
            'lokasi_masuk'     => $lokasi,
            'keterangan'       => $keterangan,
        ]);
    }

    public function clockOut(Attendance $attendance, string $lokasi): Attendance
    {
        if (! $attendance->jam_masuk || $attendance->jam_pulang) {
            throw new \RuntimeException('Tidak dapat melakukan absen pulang saat ini.');
        }

        $attendance->update([
            'jam_pulang'    => Carbon::now()->toTimeString(),
            'lokasi_pulang' => $lokasi,
        ]);

        return $attendance->fresh();
    }

    public function todayAttendance(Employee $employee): ?Attendance
    {
        return Attendance::where('employee_id', $employee->id)
            ->whereDate('tanggal', today())
            ->first();
    }
}
