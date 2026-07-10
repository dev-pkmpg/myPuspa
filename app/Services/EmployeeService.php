<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EmployeeService
{
    public function create(array $data): Employee
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name'     => $data['nama_lengkap'],
                'email'    => $data['email'],
                'password' => $data['password'],
                'role'     => 'pegawai',
            ]);

            return Employee::create([
                'user_id'           => $user->id,
                'nip'               => $data['nip'],
                'nama_lengkap'      => $data['nama_lengkap'],
                'jabatan_id'        => $data['jabatan_id'] ?? null,
                'status_pegawai_id' => $data['status_pegawai_id'] ?? null,
                'klaster'           => $data['klaster'] ?? null,
                'status_aktif'      => $data['status_aktif'] ?? true,
                'tanggal_masuk'     => $data['tanggal_masuk'],
            ]);
        });
    }

    public function update(Employee $employee, array $data): Employee
    {
        return DB::transaction(function () use ($employee, $data) {
            $userUpdate = [
                'name'  => $data['nama_lengkap'],
                'email' => $data['email'],
            ];
            if (! empty($data['password'])) {
                $userUpdate['password'] = $data['password'];
            }
            $employee->user->update($userUpdate);

            $employee->update([
                'nip'               => $data['nip'],
                'nama_lengkap'      => $data['nama_lengkap'],
                'jabatan_id'        => $data['jabatan_id'] ?? null,
                'status_pegawai_id' => $data['status_pegawai_id'] ?? null,
                'klaster'           => $data['klaster'] ?? null,
                'status_aktif'      => $data['status_aktif'] ?? true,
                'tanggal_masuk'     => $data['tanggal_masuk'],
            ]);

            return $employee->fresh();
        });
    }
}
