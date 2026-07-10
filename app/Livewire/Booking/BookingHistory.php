<?php

namespace App\Livewire\Booking;

use App\Models\BookingRuangan;
use Livewire\Component;

class BookingHistory extends Component
{
    public function cancel(int $id): void
    {
        $booking = BookingRuangan::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($booking->status !== 'pending') {
            session()->flash('error', 'Hanya booking dengan status pending yang dapat dibatalkan.');
            return;
        }

        $booking->update(['status' => 'rejected', 'catatan_manager' => 'Dibatalkan oleh pemohon.']);
        session()->flash('success', 'Booking berhasil dibatalkan.');
    }

    public function render()
    {
        return view('livewire.booking.booking-history', [
            'bookings' => BookingRuangan::with('ruangan')
                ->where('user_id', auth()->id())
                ->orderByDesc('tanggal')
                ->orderByDesc('jam_mulai')
                ->get(),
        ]);
    }
}
