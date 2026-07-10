<?php

namespace App\Models\Zkteco;

use Illuminate\Database\Eloquent\Model;

class CheckInOut extends Model
{
    protected $connection = 'zkteco';
    protected $table = 'CHECKINOUT';
    public $timestamps = false;

    protected $casts = [
        'CHECKTIME' => 'datetime',
    ];
}
