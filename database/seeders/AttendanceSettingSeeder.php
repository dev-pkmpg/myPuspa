<?php

namespace Database\Seeders;

use App\Models\AttendanceSetting;
use Illuminate\Database\Seeder;

class AttendanceSettingSeeder extends Seeder
{
    public function run(): void
    {
        AttendanceSetting::firstOrCreate(
            ['nama_shift' => 'Shift Reguler'],
            [
                'jam_masuk_mulai'   => '07:00:00',
                'jam_masuk_selesai' => '08:00:00',
                'jam_pulang_mulai'  => '16:00:00',
                'status_aktif'      => true,
            ]
        );
    }
}
