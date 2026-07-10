<?php

namespace Tests\Feature\Livewire\Ruangan;

use App\Livewire\Ruangan\RuanganManager;
use App\Models\BookingRuangan;
use App\Models\Ruangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RuanganManagerTest extends TestCase
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
        Livewire::test(RuanganManager::class)->assertStatus(200);
    }

    public function test_can_add_ruangan(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(RuanganManager::class)
            ->set('nama', 'Ruang Rapat A')
            ->set('kapasitas', 10)
            ->set('lokasi', 'Lantai 2')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ruangans', ['nama' => 'Ruang Rapat A', 'kapasitas' => 10]);
    }

    public function test_save_validates_required_fields(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(RuanganManager::class)
            ->set('nama', '')
            ->set('kapasitas', '')
            ->call('save')
            ->assertHasErrors(['nama' => 'required', 'kapasitas' => 'required']);
    }

    public function test_can_edit_ruangan(): void
    {
        $ruangan = Ruangan::create(['nama' => 'Nama Lama', 'kapasitas' => 5, 'aktif' => true]);
        $this->actingAs($this->admin);

        Livewire::test(RuanganManager::class)
            ->call('edit', $ruangan->id)
            ->set('nama', 'Nama Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ruangans', ['id' => $ruangan->id, 'nama' => 'Nama Baru']);
    }

    public function test_can_toggle_aktif(): void
    {
        $ruangan = Ruangan::create(['nama' => 'Ruang A', 'kapasitas' => 5, 'aktif' => true]);
        $this->actingAs($this->admin);

        Livewire::test(RuanganManager::class)->call('toggleAktif', $ruangan->id);

        $this->assertFalse($ruangan->fresh()->aktif);
    }

    public function test_cannot_delete_ruangan_with_active_booking(): void
    {
        $ruangan = Ruangan::create(['nama' => 'Ruang A', 'kapasitas' => 5, 'aktif' => true]);
        $user = User::factory()->create(['role' => 'pegawai']);
        BookingRuangan::create([
            'ruangan_id'  => $ruangan->id,
            'user_id'     => $user->id,
            'tanggal'     => today()->toDateString(),
            'jam_mulai'   => '09:00:00',
            'jam_selesai' => '10:00:00',
            'keperluan'   => 'Rapat',
            'status'      => 'pending',
        ]);
        $this->actingAs($this->admin);

        Livewire::test(RuanganManager::class)->call('delete', $ruangan->id);

        $this->assertDatabaseHas('ruangans', ['id' => $ruangan->id]);
    }

    public function test_can_delete_ruangan_without_active_booking(): void
    {
        $ruangan = Ruangan::create(['nama' => 'Ruang A', 'kapasitas' => 5, 'aktif' => true]);
        $this->actingAs($this->admin);

        Livewire::test(RuanganManager::class)->call('delete', $ruangan->id);

        $this->assertDatabaseMissing('ruangans', ['id' => $ruangan->id]);
    }
}
