<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@puspa.test'],
            [
                'nrk'               => 'ADMIN',
                'password'          => 'password',
                'role'              => 'admin',
                'email_verified_at' => now(),
            ]
        );

        $pegawai = [
            ['nrk' => 'NRK001', 'nip' => '19850101001', 'nama_lengkap' => 'Siti Rahayu',     'email' => 'siti@puspa.test',  'tanggal_masuk' => '2020-03-01'],
            ['nrk' => 'NRK002', 'nip' => '19880215002', 'nama_lengkap' => 'Ahmad Fauzi',     'email' => 'ahmad@puspa.test', 'tanggal_masuk' => '2019-07-15'],
            ['nrk' => 'NRK003', 'nip' => '19900522003', 'nama_lengkap' => 'Dewi Lestari',    'email' => 'dewi@puspa.test',  'tanggal_masuk' => '2021-01-10'],
            ['nrk' => 'NRK004', 'nip' => '19921130004', 'nama_lengkap' => 'Rizki Pratama',   'email' => 'rizki@puspa.test', 'tanggal_masuk' => '2022-06-01'],
            ['nrk' => 'NRK005', 'nip' => '19950807005', 'nama_lengkap' => 'Putri Handayani', 'email' => 'putri@puspa.test', 'tanggal_masuk' => '2023-02-20'],
        ];

        foreach ($pegawai as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'nrk'               => $data['nrk'],
                    'password'          => 'password',
                    'role'              => 'pegawai',
                    'email_verified_at' => now(),
                ]
            );

            Employee::firstOrCreate(
                ['nip' => $data['nip']],
                [
                    'user_id'       => $user->id,
                    'nama_lengkap'  => $data['nama_lengkap'],
                    'status_aktif'  => true,
                    'tanggal_masuk' => $data['tanggal_masuk'],
                ]
            );
        }
    }
}
