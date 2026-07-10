<?php

namespace Database\Seeders;

use App\Models\Klaster;
use Illuminate\Database\Seeder;

class KlasterSeeder extends Seeder
{
    public function run(): void
    {
        $klasters = [
            ['nama_klaster' => 'Klaster 1',      'keterangan' => null],
            ['nama_klaster' => 'Klaster 2',      'keterangan' => null],
            ['nama_klaster' => 'Klaster 3',      'keterangan' => null],
            ['nama_klaster' => 'Klaster 4',      'keterangan' => null],
            ['nama_klaster' => 'Lintas Klaster', 'keterangan' => null],
        ];

        foreach ($klasters as $data) {
            Klaster::firstOrCreate(
                ['nama_klaster' => $data['nama_klaster']],
                ['keterangan' => $data['keterangan'], 'aktif' => true]
            );
        }
    }
}
