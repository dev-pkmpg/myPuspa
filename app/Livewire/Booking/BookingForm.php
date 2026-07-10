<?php

namespace App\Livewire\Booking;

use App\Models\BookingRuangan;
use App\Models\Ruangan;
use Livewire\Component;

class BookingForm extends Component
{
    public string $ruangan_id = '';
    public string $tanggal = '';
    public string $jam_mulai = '';
    public string $jam_selesai = '';
    public string $keperluan = '';

    protected $rules = [
        'ruangan_id'  => 'required|exists:ruangans,id',
        'tanggal'     => 'required|date',
        'jam_mulai'   => 'required|date_format:H:i',
        'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
        'keperluan'   => 'required|string|max:255',
    ];

    public function save(): void
    {
        $this->validate();

        $ada_konflik = BookingRuangan::bentrok(
            (int) $this->ruangan_id,
            $this->tanggal,
            $this->jam_mulai . ':00',
            $this->jam_selesai . ':00'
        )->exists();

        if ($ada_konflik) {
            $this->addError('jam_mulai', 'Ruangan sudah dibooking pada jam tersebut. Pilih waktu lain.');
            return;
        }

        BookingRuangan::create([
            'ruangan_id'  => (int) $this->ruangan_id,
            'user_id'     => auth()->id(),
            'tanggal'     => $this->tanggal,
            'jam_mulai'   => $this->jam_mulai . ':00',
            'jam_selesai' => $this->jam_selesai . ':00',
            'keperluan'   => $this->keperluan,
            'status'      => 'pending',
        ]);

        session()->flash('success', 'Booking berhasil diajukan. Menunggu persetujuan manager.');
        $this->reset(['ruangan_id', 'tanggal', 'jam_mulai', 'jam_selesai', 'keperluan']);
    }

    public function render()
    {
        return view('livewire.booking.booking-form', [
            'ruangans' => Ruangan::aktif()->orderBy('nama')->get(),
        ]);
    }
}
