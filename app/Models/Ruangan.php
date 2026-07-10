<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ruangan extends Model
{
    protected $fillable = ['nama', 'kapasitas', 'lokasi', 'aktif'];

    protected $casts = ['aktif' => 'boolean'];

    public function bookings(): HasMany
    {
        return $this->hasMany(BookingRuangan::class);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }
}
