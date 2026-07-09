<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Attendance\ClockInOut;
use App\Models\AttendanceSetting;
use App\Models\Employee;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClockInOutTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'pegawai']);
        $this->employee = Employee::create([
            'user_id' => $this->user->id, 'nip' => '999',
            'nama_lengkap' => 'Test User', 'status_aktif' => true, 'tanggal_masuk' => today(),
        ]);

        AttendanceSetting::create([
            'nama_shift' => 'Shift Pagi', 'jam_masuk_mulai' => '07:00:00',
            'jam_masuk_selesai' => '08:00:00', 'jam_pulang_mulai' => '16:00:00', 'status_aktif' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_renders_clock_in_button_when_not_clocked_in(): void
    {
        $this->actingAs($this->user);
        Livewire::test(ClockInOut::class)->assertSee('Absen Masuk');
    }

    public function test_clock_in_creates_record(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(7, 30));
        $this->actingAs($this->user);

        Livewire::test(ClockInOut::class)
            ->set('lokasi', 'Kantor Pusat')
            ->call('clockIn')
            ->assertHasNoErrors()
            ->assertSee('Absen Pulang');

        $this->assertDatabaseHas('attendances', ['employee_id' => $this->employee->id]);
    }

    public function test_shows_clock_out_after_clock_in(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(7, 30));
        $this->actingAs($this->user);
        (new AttendanceService())->clockIn($this->employee, 'Kantor');

        Livewire::test(ClockInOut::class)
            ->assertSee('Absen Pulang')
            ->assertDontSee('Absen Masuk');
    }

    public function test_clock_in_requires_lokasi(): void
    {
        $this->actingAs($this->user);
        Livewire::test(ClockInOut::class)
            ->set('lokasi', '')
            ->call('clockIn')
            ->assertHasErrors(['lokasi' => 'required']);
    }

    public function test_clock_out_updates_record(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(7, 30));
        $this->actingAs($this->user);
        (new AttendanceService())->clockIn($this->employee, 'Kantor');

        Carbon::setTestNow(Carbon::today()->setTime(16, 0));

        Livewire::test(ClockInOut::class)
            ->set('lokasi', 'Kantor')
            ->call('clockOut')
            ->assertHasNoErrors()
            ->assertSee('Absensi hari ini selesai');

        $this->assertNotNull(
            $this->employee->attendances()->whereDate('tanggal', today())->first()?->jam_pulang
        );
    }
}
