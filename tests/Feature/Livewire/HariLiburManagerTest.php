<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Settings\HariLiburManager;
use App\Models\HariLibur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HariLiburManagerTest extends TestCase
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
        Livewire::test(HariLiburManager::class)->assertStatus(200);
    }

    public function test_can_add_hari_libur(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(HariLiburManager::class)
            ->set('tanggal', '2026-08-17')
            ->set('nama', 'HUT RI ke-81')
            ->set('keterangan', 'Hari Kemerdekaan')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('hari_liburs', ['tanggal' => '2026-08-17', 'nama' => 'HUT RI ke-81']);
    }

    public function test_save_validates_required_fields(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(HariLiburManager::class)
            ->set('tanggal', '')
            ->set('nama', '')
            ->call('save')
            ->assertHasErrors(['tanggal' => 'required', 'nama' => 'required']);
    }

    public function test_cannot_add_duplicate_tanggal(): void
    {
        HariLibur::create(['tanggal' => '2026-08-17', 'nama' => 'HUT RI ke-81']);
        $this->actingAs($this->admin);

        Livewire::test(HariLiburManager::class)
            ->set('tanggal', '2026-08-17')
            ->set('nama', 'Nama Lain')
            ->call('save')
            ->assertHasErrors(['tanggal' => 'unique']);
    }

    public function test_can_edit_hari_libur(): void
    {
        $libur = HariLibur::create(['tanggal' => '2026-08-17', 'nama' => 'Nama Lama']);
        $this->actingAs($this->admin);

        Livewire::test(HariLiburManager::class)
            ->call('edit', $libur->id)
            ->set('nama', 'Nama Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('hari_liburs', ['id' => $libur->id, 'nama' => 'Nama Baru']);
    }

    public function test_can_delete_hari_libur(): void
    {
        $libur = HariLibur::create(['tanggal' => '2026-08-17', 'nama' => 'HUT RI ke-81']);
        $this->actingAs($this->admin);

        Livewire::test(HariLiburManager::class)->call('delete', $libur->id);

        $this->assertDatabaseMissing('hari_liburs', ['id' => $libur->id]);
    }
}
