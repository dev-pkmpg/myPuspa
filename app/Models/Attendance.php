<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id', 'tanggal', 'jam_masuk', 'jam_pulang',
        'status_kehadiran', 'lokasi_masuk', 'lokasi_pulang', 'keterangan',
    ];

    protected $casts = ['tanggal' => 'date'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
