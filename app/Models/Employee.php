<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    protected $fillable = [
        'user_id', 'nip', 'nik', 'npwp',
        'nomor_bpjs_ketenagakerjaan', 'nomor_bpjs_kesehatan',
        'id_sip', 'id_str', 'nomor_hp', 'email_pribadi', 'status_pernikahan',
        'nama_lengkap', 'status_aktif', 'tanggal_masuk',
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

    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeAssignment::class)->orderByDesc('tanggal_mulai');
    }

    public function currentAssignment(): HasOne
    {
        return $this->hasOne(EmployeeAssignment::class)
            ->whereNull('tanggal_selesai')
            ->latestOfMany('tanggal_mulai');
    }
}
