<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSetting extends Model
{
    protected $fillable = [
        'nama_shift', 'jam_masuk_mulai', 'jam_masuk_selesai',
        'jam_pulang_mulai', 'status_aktif',
    ];

    protected $casts = ['status_aktif' => 'boolean'];
}
