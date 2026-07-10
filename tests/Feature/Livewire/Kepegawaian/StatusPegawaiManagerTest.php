<?php

namespace Tests\Feature\Livewire\Kepegawaian;

use App\Livewire\Kepegawaian\StatusPegawaiManager;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\StatusPegawai;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StatusPegawaiManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_component_renders(): void
    {
        $this->actingAs($this->admin);
        Livewire::test(StatusPegawaiManager::class)->assertStatus(200);
    }

    public function test_can_add_status_pegawai(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(StatusPegawaiManager::class)
            ->set('nama_status', 'PNS')
            ->set('keterangan', 'Pegawai Negeri Sipil')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('status_pegawais', ['nama_status' => 'PNS']);
    }

    public function test_save_validates_required_fields(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(StatusPegawaiManager::class)
            ->set('nama_status', '')
            ->call('save')
            ->assertHasErrors(['nama_status' => 'required']);
    }

    public function test_can_edit_status_pegawai(): void
    {
        $status = StatusPegawai::create(['nama_status' => 'Nama Lama', 'aktif' => true]);
        $this->actingAs($this->admin);

        Livewire::test(StatusPegawaiManager::class)
            ->call('edit', $status->id)
            ->set('nama_status', 'Nama Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('status_pegawais', ['id' => $status->id, 'nama_status' => 'Nama Baru']);
    }

    public function test_can_toggle_aktif(): void
    {
        $status = StatusPegawai::create(['nama_status' => 'PNS', 'aktif' => true]);
        $this->actingAs($this->admin);

        Livewire::test(StatusPegawaiManager::class)->call('toggleAktif', $status->id);

        $this->assertFalse($status->fresh()->aktif);
    }

    public function test_cannot_delete_status_used_by_employee(): void
    {
        $status = StatusPegawai::create(['nama_status' => 'PNS', 'aktif' => true]);
        $user = User::factory()->create(['role' => 'pegawai']);
        $employee = Employee::create([
            'user_id' => $user->id, 'nip' => '001', 'nama_lengkap' => 'Test', 'tanggal_masuk' => today(),
        ]);
        EmployeeAssignment::create([
            'employee_id' => $employee->id, 'status_pegawai_id' => $status->id, 'tanggal_mulai' => today(),
        ]);
        $this->actingAs($this->admin);

        Livewire::test(StatusPegawaiManager::class)->call('delete', $status->id);

        $this->assertDatabaseHas('status_pegawais', ['id' => $status->id]);
    }

    public function test_can_delete_unused_status(): void
    {
        $status = StatusPegawai::create(['nama_status' => 'PNS', 'aktif' => true]);
        $this->actingAs($this->admin);

        Livewire::test(StatusPegawaiManager::class)->call('delete', $status->id);

        $this->assertDatabaseMissing('status_pegawais', ['id' => $status->id]);
    }
}
