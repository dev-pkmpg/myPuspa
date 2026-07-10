<?php

namespace Database\Seeders;

use App\Models\Lokasi;
use Illuminate\Database\Seeder;

class LokasiSeeder extends Seeder
{
    public function run(): void
    {
        $lokasis = [
            ['nama_lokasi' => 'Kantor Pusat',  'keterangan' => null, 'is_pkc' => false],
            ['nama_lokasi' => 'Gedung A',      'keterangan' => null, 'is_pkc' => false],
            ['nama_lokasi' => 'Gedung B',      'keterangan' => null, 'is_pkc' => false],
            ['nama_lokasi' => 'PKC Utama',     'keterangan' => null, 'is_pkc' => true],
            ['nama_lokasi' => 'PKC Cabang',    'keterangan' => null, 'is_pkc' => true],
        ];

        foreach ($lokasis as $data) {
            Lokasi::firstOrCreate(
                ['nama_lokasi' => $data['nama_lokasi']],
                ['keterangan' => $data['keterangan'], 'aktif' => true, 'is_pkc' => $data['is_pkc']]
            );
        }
    }
}
