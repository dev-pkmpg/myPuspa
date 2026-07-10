<?php

namespace App\Livewire\Absensi;

use App\Models\User;
use App\Models\Zkteco\CheckInOut;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ZktecoAbsensi extends Component
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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTanggalDari(): void
    {
        $this->resetPage();
    }

    public function updatedTanggalSampai(): void
    {
        $this->resetPage();
    }

    private function checkConnection(): void
    {
        try {
            DB::connection('zkteco')->getPdo();
            $this->connected = true;
        } catch (\Throwable $e) {
            $this->connected        = false;
            $this->connectionError  = $e->getMessage();
        }
    }

    private function nrkMap(): Collection
    {
        return User::whereNotNull('nrk')->pluck('nrk', 'nrk');
    }

    public function render()
    {
        $records = collect();
        $nrkToName = collect();

        if ($this->connected) {
            $query = CheckInOut::on('zkteco')
                ->select('USERID', 'CHECKTIME', 'CHECKTYPE')
                ->whereBetween('CHECKTIME', [
                    $this->tanggal_dari . ' 00:00:00',
                    $this->tanggal_sampai . ' 23:59:59',
                ]);

            if ($this->search !== '') {
                $query->where('USERID', 'like', '%' . $this->search . '%');
            }

            $query->orderByDesc('CHECKTIME');

            // Ambil NRK dari DB utama untuk lookup nama pegawai
            $userids = (clone $query)->distinct()->pluck('USERID');
            $nrkToName = User::whereIn('nrk', $userids)
                ->with('employee:user_id,nama_lengkap')
                ->get()
                ->mapWithKeys(fn ($u) => [$u->nrk => $u->employee?->nama_lengkap ?? $u->nrk]);

            $records = $query->paginate(50);
        }

        return view('livewire.absensi.zkteco-absensi', [
            'records'    => $records,
            'nrkToName'  => $nrkToName,
        ]);
    }
}
