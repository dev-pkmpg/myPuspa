<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingRuangan extends Model
{
    protected $fillable = [
        'ruangan_id', 'user_id', 'tanggal', 'jam_mulai',
        'jam_selesai', 'keperluan', 'status', 'catatan_manager',
    ];

    protected $casts = ['tanggal' => 'date:Y-m-d'];

    public function ruangan(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeBentrok(Builder $query, int $ruanganId, string $tanggal, string $jamMulai, string $jamSelesai, ?int $exceptId = null): Builder
    {
        return $query
            ->where('ruangan_id', $ruanganId)
            ->whereDate('tanggal', $tanggal)
            ->whereIn('status', ['pending', 'approved'])
            ->where('jam_mulai', '<', $jamSelesai)
            ->where('jam_selesai', '>', $jamMulai)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId));
    }
}
