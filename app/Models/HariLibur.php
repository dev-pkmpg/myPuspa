<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HariLibur extends Model
{
    protected $fillable = ['tanggal', 'nama', 'keterangan'];

    protected $casts = ['tanggal' => 'date'];

    public function scopeOnDate(Builder $query, mixed $date): Builder
    {
        return $query->whereDate('tanggal', $date);
    }
}
