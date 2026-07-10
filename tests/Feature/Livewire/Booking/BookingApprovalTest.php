<?php

namespace Tests\Feature\Livewire\Booking;

use App\Livewire\Booking\BookingApproval;
use App\Models\BookingRuangan;
use App\Models\Ruangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BookingApprovalTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private BookingRuangan $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = User::factory()->create(['role' => 'manager']);
        $pegawai       = User::factory()->create(['role' => 'pegawai']);
        $ruangan       = Ruangan::create(['nama' => 'Ruang A', 'kapasitas' => 10, 'aktif' => true]);
        $this->booking = BookingRuangan::create([
            'ruangan_id'  => $ruangan->id,
            'user_id'     => $pegawai->id,
            'tanggal'     => today()->addDay()->toDateString(),
            'jam_mulai'   => '09:00:00',
            'jam_selesai' => '10:00:00',
            'keperluan'   => 'Rapat',
            'status'      => 'pending',
        ]);
    }

    public function test_component_renders_for_manager(): void
    {
        $this->actingAs($this->manager);
        Livewire::test(BookingApproval::class)->assertStatus(200);
    }

    public function test_can_approve_booking(): void
    {
        $this->actingAs($this->manager);

        Livewire::test(BookingApproval::class)->call('approve', $this->booking->id);

        $this->assertDatabaseHas('booking_ruangans', ['id' => $this->booking->id, 'status' => 'approved']);
    }

    public function test_can_reject_booking_with_catatan(): void
    {
        $this->actingAs($this->manager);

        Livewire::test(BookingApproval::class)
            ->call('reject', $this->booking->id, 'Ruangan sedang dalam perbaikan.');

        $this->assertDatabaseHas('booking_ruangans', [
            'id'              => $this->booking->id,
            'status'          => 'rejected',
            'catatan_manager' => 'Ruangan sedang dalam perbaikan.',
        ]);
    }

    public function test_non_manager_cannot_approve(): void
    {
        $pegawai = User::factory()->create(['role' => 'pegawai']);
        $this->actingAs($pegawai);

        Livewire::test(BookingApproval::class)
            ->call('approve', $this->booking->id)
            ->assertStatus(403);
    }
}
