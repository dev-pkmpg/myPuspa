<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StatusPegawai extends Model
{
    protected $fillable = ['nama_status', 'keterangan', 'aktif'];

    protected $casts = ['aktif' => 'boolean'];

    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeAssignment::class);
    }
}
