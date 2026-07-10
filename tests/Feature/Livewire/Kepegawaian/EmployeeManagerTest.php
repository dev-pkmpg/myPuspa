<?php

namespace Tests\Feature\Livewire\Kepegawaian;

use App\Livewire\Kepegawaian\EmployeeManager;
use App\Models\Employee;
use App\Models\Jabatan;
use App\Models\StatusPegawai;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Jabatan $jabatan;
    private StatusPegawai $statusPegawai;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin         = User::factory()->create(['role' => 'admin']);
        $this->jabatan       = Jabatan::create(['nama_jabatan' => 'Staff', 'aktif' => true]);
        $this->statusPegawai = StatusPegawai::create(['nama_status' => 'PNS', 'aktif' => true]);
    }

    public function test_component_renders(): void
    {
        $this->actingAs($this->admin);
        Livewire::test(EmployeeManager::class)->assertStatus(200);
    }

    public function test_can_add_employee(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(EmployeeManager::class)
            ->set('nama_lengkap', 'Budi Santoso')
            ->set('email', 'budi@example.com')
            ->set('password', 'password123')
            ->set('nip', '001')
            ->set('tanggal_masuk', '2024-01-01')
            ->set('jabatan_id', $this->jabatan->id)
            ->set('status_pegawai_id', $this->statusPegawai->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employees', ['nip' => '001']);
        $this->assertDatabaseHas('employee_assignments', ['jabatan_id' => $this->jabatan->id, 'status_pegawai_id' => $this->statusPegawai->id]);
        $this->assertDatabaseHas('users', ['email' => 'budi@example.com', 'role' => 'pegawai']);
    }

    public function test_save_validates_required_fields(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(EmployeeManager::class)
            ->set('nama_lengkap', '')
            ->set('email', '')
            ->set('password', '')
            ->set('nip', '')
            ->set('tanggal_masuk', '')
            ->call('save')
            ->assertHasErrors(['nama_lengkap', 'email', 'password', 'nip', 'tanggal_masuk']);
    }

    public function test_can_edit_employee_without_changing_password(): void
    {
        $user = User::factory()->create(['role' => 'pegawai', 'email' => 'old@example.com']);
        $employee = Employee::create([
            'user_id' => $user->id, 'nip' => '001', 'nama_lengkap' => 'Nama Lama',
            'tanggal_masuk' => today(),
        ]);
        $oldHash = $user->fresh()->password;
        $this->actingAs($this->admin);

        Livewire::test(EmployeeManager::class)
            ->call('edit', $employee->id)
            ->set('nama_lengkap', 'Nama Baru')
            ->set('password', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'nama_lengkap' => 'Nama Baru']);
        $this->assertEquals($oldHash, $user->fresh()->password);
    }

    public function test_can_toggle_status_aktif(): void
    {
        $user = User::factory()->create(['role' => 'pegawai']);
        $employee = Employee::create([
            'user_id' => $user->id, 'nip' => '001', 'nama_lengkap' => 'Test',
            'tanggal_masuk' => today(), 'status_aktif' => true,
        ]);
        $this->actingAs($this->admin);

        Livewire::test(EmployeeManager::class)->call('toggleStatusAktif', $employee->id);

        $this->assertFalse($employee->fresh()->status_aktif);
    }

    public function test_can_delete_employee(): void
    {
        $user = User::factory()->create(['role' => 'pegawai']);
        $employee = Employee::create([
            'user_id' => $user->id, 'nip' => '001', 'nama_lengkap' => 'Test',
            'tanggal_masuk' => today(),
        ]);
        $this->actingAs($this->admin);

        Livewire::test(EmployeeManager::class)->call('delete', $employee->id);

        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
    }

    public function test_invalid_klaster_fails_validation(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(EmployeeManager::class)
            ->set('nama_lengkap', 'Budi')
            ->set('email', 'budi@example.com')
            ->set('password', 'password123')
            ->set('nip', '001')
            ->set('tanggal_masuk', '2024-01-01')
            ->set('klaster_id', 9999)
            ->call('save')
            ->assertHasErrors(['klaster_id']);
    }
}
