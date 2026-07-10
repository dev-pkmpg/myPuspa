<?php

namespace Tests\Feature\Livewire\Kepegawaian;

use App\Livewire\Kepegawaian\KlasterManager;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Klaster;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KlasterManagerTest extends TestCase
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
        Livewire::test(KlasterManager::class)->assertStatus(200);
    }

    public function test_can_add_klaster(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(KlasterManager::class)
            ->set('nama_klaster', 'Klaster 5')
            ->set('keterangan', 'Klaster baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('klasters', ['nama_klaster' => 'Klaster 5']);
    }

    public function test_nama_klaster_must_be_unique(): void
    {
        Klaster::create(['nama_klaster' => 'Klaster 1', 'aktif' => true]);
        $this->actingAs($this->admin);

        Livewire::test(KlasterManager::class)
            ->set('nama_klaster', 'Klaster 1')
            ->call('save')
            ->assertHasErrors(['nama_klaster']);
    }

    public function test_can_edit_klaster(): void
    {
        $klaster = Klaster::create(['nama_klaster' => 'Klaster Lama', 'aktif' => true]);
        $this->actingAs($this->admin);

        Livewire::test(KlasterManager::class)
            ->call('edit', $klaster->id)
            ->set('nama_klaster', 'Klaster Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('klasters', ['id' => $klaster->id, 'nama_klaster' => 'Klaster Baru']);
    }

    public function test_can_toggle_aktif(): void
    {
        $klaster = Klaster::create(['nama_klaster' => 'Klaster X', 'aktif' => true]);
        $this->actingAs($this->admin);

        Livewire::test(KlasterManager::class)->call('toggleAktif', $klaster->id);

        $this->assertFalse($klaster->fresh()->aktif);
    }

    public function test_cannot_delete_klaster_used_by_employee(): void
    {
        $klaster = Klaster::create(['nama_klaster' => 'Klaster X', 'aktif' => true]);
        $user = User::factory()->create(['role' => 'pegawai']);
        $employee = Employee::create([
            'user_id' => $user->id, 'nip' => '001', 'nama_lengkap' => 'Test', 'tanggal_masuk' => today(),
        ]);
        EmployeeAssignment::create([
            'employee_id' => $employee->id, 'klaster_id' => $klaster->id, 'tanggal_mulai' => today(),
        ]);
        $this->actingAs($this->admin);

        Livewire::test(KlasterManager::class)->call('delete', $klaster->id);

        $this->assertDatabaseHas('klasters', ['id' => $klaster->id]);
    }

    public function test_can_delete_unused_klaster(): void
    {
        $klaster = Klaster::create(['nama_klaster' => 'Klaster X', 'aktif' => true]);
        $this->actingAs($this->admin);

        Livewire::test(KlasterManager::class)->call('delete', $klaster->id);

        $this->assertDatabaseMissing('klasters', ['id' => $klaster->id]);
    }
}
