<?php

namespace Tests\Feature\Livewire\Booking;

use App\Livewire\Booking\BookingHistory;
use App\Models\BookingRuangan;
use App\Models\Ruangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BookingHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Ruangan $ruangan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user    = User::factory()->create(['role' => 'pegawai']);
        $this->ruangan = Ruangan::create(['nama' => 'Ruang A', 'kapasitas' => 10, 'aktif' => true]);
    }

    public function test_component_renders(): void
    {
        $this->actingAs($this->user);
        Livewire::test(BookingHistory::class)->assertStatus(200);
    }

    public function test_shows_only_own_bookings(): void
    {
        $other = User::factory()->create(['role' => 'pegawai']);
        BookingRuangan::create([
            'ruangan_id' => $this->ruangan->id, 'user_id' => $this->user->id,
            'tanggal' => today()->toDateString(), 'jam_mulai' => '09:00:00',
            'jam_selesai' => '10:00:00', 'keperluan' => 'Rapat Saya', 'status' => 'pending',
        ]);
        BookingRuangan::create([
            'ruangan_id' => $this->ruangan->id, 'user_id' => $other->id,
            'tanggal' => today()->toDateString(), 'jam_mulai' => '11:00:00',
            'jam_selesai' => '12:00:00', 'keperluan' => 'Rapat Orang Lain', 'status' => 'pending',
        ]);
        $this->actingAs($this->user);

        Livewire::test(BookingHistory::class)
            ->assertSee('Rapat Saya')
            ->assertDontSee('Rapat Orang Lain');
    }

    public function test_can_cancel_pending_booking(): void
    {
        $booking = BookingRuangan::create([
            'ruangan_id' => $this->ruangan->id, 'user_id' => $this->user->id,
            'tanggal' => today()->toDateString(), 'jam_mulai' => '09:00:00',
            'jam_selesai' => '10:00:00', 'keperluan' => 'Rapat', 'status' => 'pending',
        ]);
        $this->actingAs($this->user);

        Livewire::test(BookingHistory::class)->call('cancel', $booking->id);

        $this->assertDatabaseHas('booking_ruangans', ['id' => $booking->id, 'status' => 'rejected']);
    }

    public function test_cannot_cancel_approved_booking(): void
    {
        $booking = BookingRuangan::create([
            'ruangan_id' => $this->ruangan->id, 'user_id' => $this->user->id,
            'tanggal' => today()->toDateString(), 'jam_mulai' => '09:00:00',
            'jam_selesai' => '10:00:00', 'keperluan' => 'Rapat', 'status' => 'approved',
        ]);
        $this->actingAs($this->user);

        Livewire::test(BookingHistory::class)->call('cancel', $booking->id);

        $this->assertDatabaseHas('booking_ruangans', ['id' => $booking->id, 'status' => 'approved']);
    }
}
