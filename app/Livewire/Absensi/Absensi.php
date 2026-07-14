<?php

namespace App\Livewire\Absensi;

use App\Models\TbAbsen;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Absensi extends Component
{
    use WithPagination;

    public string $tanggal_dari = '';
    public string $tanggal_sampai = '';
    public string $search = '';
    public bool $connected = false;
    public string $connectionError = '';

    public function mount(): void
    {
        $this->tanggal_dari   = now()->startOfMonth()->format('Y-m-d');
        $this->tanggal_sampai = now()->format('Y-m-d');
        $this->checkConnection();
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedTanggalDari(): void { $this->resetPage(); }
    public function updatedTanggalSampai(): void { $this->resetPage(); }

    private function checkConnection(): void
    {
        try {
            DB::connection('attendance')->getPdo();
            $this->connected = true;
        } catch (\Throwable $e) {
            $this->connected       = false;
            $this->connectionError = $e->getMessage();
        }
    }

    public function render()
    {
        $records   = collect();
        $nrkToName = collect();

        if ($this->connected) {
            $isAdmin = auth()->user()->isAdmin();

            $query = TbAbsen::query()
                ->whereBetween('date_time', [
                    $this->tanggal_dari . ' 00:00:00',
                    $this->tanggal_sampai . ' 23:59:59',
                ]);

            if ($isAdmin) {
                if ($this->search !== '') {
                    $query->where('pin', 'like', '%' . $this->search . '%');
                }
            } else {
                $query->where('pin', auth()->user()->nrk);
            }

            $query->orderByDesc('date_time');

            $pins      = (clone $query)->distinct()->pluck('pin');
            $nrkToName = User::whereIn('nrk', $pins)
                ->with('employee:user_id,nama_lengkap')
                ->get()
                ->mapWithKeys(fn ($u) => [$u->nrk => $u->employee?->nama_lengkap ?? $u->nrk]);

            $records = $query->paginate(50);
        }

        return view('livewire.absensi.absensi', [
            'records'   => $records,
            'nrkToName' => $nrkToName,
            'isAdmin'   => auth()->user()->isAdmin(),
        ]);
    }
}
