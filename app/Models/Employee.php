<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'user_id', 'nip', 'nrk', 'nama_lengkap', 'status_aktif', 'tanggal_masuk',
        'jabatan_id', 'status_pegawai_id', 'klaster',
    ];

    protected $casts = [
        'status_aktif'  => 'boolean',
        'tanggal_masuk' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }

    public function statusPegawai(): BelongsTo
    {
        return $this->belongsTo(StatusPegawai::class);
    }
}
