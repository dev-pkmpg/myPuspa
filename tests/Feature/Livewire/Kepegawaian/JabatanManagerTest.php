<?php

namespace Tests\Feature\Livewire\Kepegawaian;

use App\Livewire\Kepegawaian\JabatanManager;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Jabatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JabatanManagerTest extends TestCase
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
        Livewire::test(JabatanManager::class)->assertStatus(200);
    }

    public function test_can_add_jabatan(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(JabatanManager::class)
            ->set('nama_jabatan', 'Staff IT')
            ->set('keterangan', 'Tim Teknologi')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('jabatans', ['nama_jabatan' => 'Staff IT']);
    }

    public function test_save_validates_required_fields(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(JabatanManager::class)
            ->set('nama_jabatan', '')
            ->call('save')
            ->assertHasErrors(['nama_jabatan' => 'required']);
    }

    public function test_can_edit_jabatan(): void
    {
        $jabatan = Jabatan::create(['nama_jabatan' => 'Nama Lama', 'aktif' => true]);
        $this->actingAs($this->admin);

        Livewire::test(JabatanManager::class)
            ->call('edit', $jabatan->id)
            ->set('nama_jabatan', 'Nama Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('jabatans', ['id' => $jabatan->id, 'nama_jabatan' => 'Nama Baru']);
    }

    public function test_can_toggle_aktif(): void
    {
        $jabatan = Jabatan::create(['nama_jabatan' => 'Staff IT', 'aktif' => true]);
        $this->actingAs($this->admin);

        Livewire::test(JabatanManager::class)->call('toggleAktif', $jabatan->id);

        $this->assertFalse($jabatan->fresh()->aktif);
    }

    public function test_cannot_delete_jabatan_used_by_employee(): void
    {
        $jabatan = Jabatan::create(['nama_jabatan' => 'Staff IT', 'aktif' => true]);
        $user = User::factory()->create(['role' => 'pegawai']);
        $employee = Employee::create([
            'user_id' => $user->id, 'nip' => '001', 'nama_lengkap' => 'Test', 'tanggal_masuk' => today(),
        ]);
        EmployeeAssignment::create([
            'employee_id' => $employee->id, 'jabatan_id' => $jabatan->id, 'tanggal_mulai' => today(),
        ]);
        $this->actingAs($this->admin);

        Livewire::test(JabatanManager::class)->call('delete', $jabatan->id);

        $this->assertDatabaseHas('jabatans', ['id' => $jabatan->id]);
    }

    public function test_can_delete_unused_jabatan(): void
    {
        $jabatan = Jabatan::create(['nama_jabatan' => 'Staff IT', 'aktif' => true]);
        $this->actingAs($this->admin);

        Livewire::test(JabatanManager::class)->call('delete', $jabatan->id);

        $this->assertDatabaseMissing('jabatans', ['id' => $jabatan->id]);
    }
}
