<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TbAbsen extends Model
{
    protected $connection = 'attendance';
    protected $table = 'tb_absen';
    public $timestamps = false;

    protected $fillable = [
        'pin', 'date_time', 'ver', 'status', 'id_machine', 'status_code', 'note_details',
    ];

    // status: 0 = masuk, 1 = keluar
    public function getStatusLabelAttribute(): string
    {
        return $this->status === 0 ? 'Masuk' : 'Keluar';
    }
}
