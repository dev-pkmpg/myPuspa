<?php

namespace Tests\Feature\Livewire\Booking;

use App\Livewire\Booking\BookingForm;
use App\Models\BookingRuangan;
use App\Models\Ruangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BookingFormTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Ruangan $ruangan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user    = User::factory()->create(['role' => 'pegawai']);
        $this->ruangan = Ruangan::create(['nama' => 'Ruang Rapat A', 'kapasitas' => 10, 'aktif' => true]);
    }

    public function test_component_renders(): void
    {
        $this->actingAs($this->user);
        Livewire::test(BookingForm::class)->assertStatus(200);
    }

    public function test_can_submit_booking(): void
    {
        $this->actingAs($this->user);

        Livewire::test(BookingForm::class)
            ->set('ruangan_id', $this->ruangan->id)
            ->set('tanggal', today()->addDay()->toDateString())
            ->set('jam_mulai', '09:00')
            ->set('jam_selesai', '10:00')
            ->set('keperluan', 'Rapat Tim')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('booking_ruangans', [
            'ruangan_id' => $this->ruangan->id,
            'user_id'    => $this->user->id,
            'keperluan'  => 'Rapat Tim',
            'status'     => 'pending',
        ]);
    }

    public function test_save_validates_required_fields(): void
    {
        $this->actingAs($this->user);

        Livewire::test(BookingForm::class)
            ->set('ruangan_id', '')
            ->set('tanggal', '')
            ->set('jam_mulai', '')
            ->set('jam_selesai', '')
            ->set('keperluan', '')
            ->call('save')
            ->assertHasErrors(['ruangan_id', 'tanggal', 'jam_mulai', 'jam_selesai', 'keperluan']);
    }

    public function test_save_rejects_booking_with_time_conflict(): void
    {
        BookingRuangan::create([
            'ruangan_id'  => $this->ruangan->id,
            'user_id'     => $this->user->id,
            'tanggal'     => today()->addDay()->toDateString(),
            'jam_mulai'   => '09:00:00',
            'jam_selesai' => '11:00:00',
            'keperluan'   => 'Rapat Lama',
            'status'      => 'approved',
        ]);
        $this->actingAs($this->user);

        Livewire::test(BookingForm::class)
            ->set('ruangan_id', $this->ruangan->id)
            ->set('tanggal', today()->addDay()->toDateString())
            ->set('jam_mulai', '10:00')
            ->set('jam_selesai', '11:30')
            ->set('keperluan', 'Rapat Baru')
            ->call('save')
            ->assertHasErrors(['jam_mulai']);
    }

    public function test_save_allows_booking_when_no_conflict(): void
    {
        BookingRuangan::create([
            'ruangan_id'  => $this->ruangan->id,
            'user_id'     => $this->user->id,
            'tanggal'     => today()->addDay()->toDateString(),
            'jam_mulai'   => '09:00:00',
            'jam_selesai' => '10:00:00',
            'keperluan'   => 'Rapat Lama',
            'status'      => 'approved',
        ]);
        $this->actingAs($this->user);

        Livewire::test(BookingForm::class)
            ->set('ruangan_id', $this->ruangan->id)
            ->set('tanggal', today()->addDay()->toDateString())
            ->set('jam_mulai', '10:00')
            ->set('jam_selesai', '11:00')
            ->set('keperluan', 'Rapat Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('booking_ruangans', 2);
    }
}
