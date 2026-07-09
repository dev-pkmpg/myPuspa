<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Attendance\AdminAttendanceTable;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAttendanceTableTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);

        $pegawai = User::factory()->create(['role' => 'pegawai']);
        $this->employee = Employee::create([
            'user_id' => $pegawai->id, 'nip' => '001',
            'nama_lengkap' => 'Pegawai Test', 'status_aktif' => true, 'tanggal_masuk' => today(),
        ]);
    }

    public function test_renders_for_admin(): void
    {
        $this->actingAs($this->admin);
        Livewire::test(AdminAttendanceTable::class)->assertStatus(200);
    }

    public function test_shows_todays_attendance_by_default(): void
    {
        $this->actingAs($this->admin);

        Attendance::create([
            'employee_id' => $this->employee->id, 'tanggal' => today(),
            'jam_masuk' => '07:30:00', 'status_kehadiran' => 'hadir',
        ]);

        Livewire::test(AdminAttendanceTable::class)->assertSee('Pegawai Test');
    }

    public function test_can_filter_by_status(): void
    {
        $this->actingAs($this->admin);

        Attendance::create([
            'employee_id' => $this->employee->id, 'tanggal' => today(),
            'jam_masuk' => '07:30:00', 'status_kehadiran' => 'izin',
        ]);

        Livewire::test(AdminAttendanceTable::class)
            ->set('filterStatus', 'hadir')
            ->assertDontSee('Pegawai Test');
    }
}
