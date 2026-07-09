<?php

namespace Tests\Feature\Services;

use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Employee;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private AttendanceService $service;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AttendanceService();

        $user = User::factory()->create(['role' => 'pegawai']);
        $this->employee = Employee::create([
            'user_id'      => $user->id,
            'nip'          => '111',
            'nama_lengkap' => 'Test Pegawai',
            'status_aktif' => true,
            'tanggal_masuk' => today(),
        ]);

        AttendanceSetting::create([
            'nama_shift'        => 'Shift Pagi',
            'jam_masuk_mulai'   => '07:00:00',
            'jam_masuk_selesai' => '08:00:00',
            'jam_pulang_mulai'  => '16:00:00',
            'status_aktif'      => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_clock_in_creates_hadir_record(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(7, 30));

        $attendance = $this->service->clockIn($this->employee, 'Kantor Pusat');

        $this->assertDatabaseHas('attendances', [
            'employee_id'      => $this->employee->id,
            'status_kehadiran' => 'hadir',
        ]);
        $this->assertNull($attendance->keterangan);
    }

    public function test_clock_in_marks_terlambat_after_threshold(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 30));

        $attendance = $this->service->clockIn($this->employee, 'Kantor Pusat');

        $this->assertEquals('Terlambat', $attendance->keterangan);
    }

    public function test_clock_in_throws_when_already_clocked_in(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(7, 30));
        $this->service->clockIn($this->employee, 'Kantor Pusat');

        $this->expectException(\RuntimeException::class);
        $this->service->clockIn($this->employee, 'Kantor Pusat');
    }

    public function test_clock_out_sets_jam_pulang(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(7, 30));
        $attendance = $this->service->clockIn($this->employee, 'Kantor Pusat');

        Carbon::setTestNow(Carbon::today()->setTime(16, 0));
        $result = $this->service->clockOut($attendance, 'Kantor Pusat');

        $this->assertNotNull($result->jam_pulang);
        $this->assertEquals('Kantor Pusat', $result->lokasi_pulang);
    }

    public function test_clock_out_throws_when_already_clocked_out(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(7, 30));
        $attendance = $this->service->clockIn($this->employee, 'Kantor');
        Carbon::setTestNow(Carbon::today()->setTime(16, 0));
        $this->service->clockOut($attendance, 'Kantor');

        $this->expectException(\RuntimeException::class);
        $this->service->clockOut($attendance->fresh(), 'Kantor');
    }
}
