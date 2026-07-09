<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Settings\ShiftManager;
use App\Models\AttendanceSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShiftManagerTest extends TestCase
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
        Livewire::test(ShiftManager::class)->assertStatus(200);
    }

    public function test_can_add_new_shift(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ShiftManager::class)
            ->set('nama_shift', 'Shift Siang')
            ->set('jam_masuk_mulai', '12:00')
            ->set('jam_masuk_selesai', '13:00')
            ->set('jam_pulang_mulai', '20:00')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('attendance_settings', ['nama_shift' => 'Shift Siang']);
    }

    public function test_save_validates_required_fields(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ShiftManager::class)
            ->set('nama_shift', '')
            ->call('save')
            ->assertHasErrors(['nama_shift' => 'required']);
    }

    public function test_can_toggle_shift_status(): void
    {
        $this->actingAs($this->admin);

        $shift = AttendanceSetting::create([
            'nama_shift' => 'Shift Test', 'jam_masuk_mulai' => '07:00',
            'jam_masuk_selesai' => '08:00', 'jam_pulang_mulai' => '16:00', 'status_aktif' => true,
        ]);

        Livewire::test(ShiftManager::class)->call('toggleStatus', $shift->id);

        $this->assertFalse($shift->fresh()->status_aktif);
    }
}
