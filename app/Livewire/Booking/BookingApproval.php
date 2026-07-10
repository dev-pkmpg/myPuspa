<?php

namespace App\Livewire\Booking;

use App\Models\BookingRuangan;
use Livewire\Component;

class BookingApproval extends Component
{
    public function approve(int $id): void
    {
        abort_unless(auth()->user()?->isManager(), 403);

        BookingRuangan::findOrFail($id)->update(['status' => 'approved']);
        session()->flash('success', 'Booking disetujui.');
    }

    public function reject(int $id, string $catatan = ''): void
    {
        abort_unless(auth()->user()?->isManager(), 403);

        BookingRuangan::findOrFail($id)->update([
            'status'          => 'rejected',
            'catatan_manager' => $catatan ?: null,
        ]);
        session()->flash('success', 'Booking ditolak.');
    }

    public function render()
    {
        return view('livewire.booking.booking-approval', [
            'bookings' => BookingRuangan::with(['ruangan', 'user'])
                ->pending()
                ->orderBy('tanggal')
                ->orderBy('jam_mulai')
                ->get(),
        ]);
    }
}
