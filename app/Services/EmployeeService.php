<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeAssignment;
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

            $employee = Employee::create([
                'user_id'      => $user->id,
                'nip'          => $data['nip'],
                'nrk'          => $data['nrk'] ?? null,
                'nama_lengkap' => $data['nama_lengkap'],
                'status_aktif' => $data['status_aktif'] ?? true,
                'tanggal_masuk' => $data['tanggal_masuk'],
            ]);

            EmployeeAssignment::create([
                'employee_id'       => $employee->id,
                'jabatan_id'        => $data['jabatan_id'] ?? null,
                'status_pegawai_id' => $data['status_pegawai_id'] ?? null,
                'klaster_id'        => $data['klaster_id'] ?? null,
                'tanggal_mulai'     => $data['tanggal_masuk'],
            ]);

            return $employee;
        });
    }

    public function update(Employee $employee, array $data): Employee
    {
        return DB::transaction(function () use ($employee, $data) {
            $userUpdate = ['name' => $data['nama_lengkap'], 'email' => $data['email']];
            if (! empty($data['password'])) {
                $userUpdate['password'] = $data['password'];
            }
            $employee->user->update($userUpdate);

            $employee->update([
                'nip'          => $data['nip'],
                'nrk'          => $data['nrk'] ?? null,
                'nama_lengkap' => $data['nama_lengkap'],
                'status_aktif' => $data['status_aktif'] ?? true,
                'tanggal_masuk' => $data['tanggal_masuk'],
            ]);

            $current = $employee->currentAssignment;
            $newJabatan  = $data['jabatan_id'] ?? null;
            $newStatus   = $data['status_pegawai_id'] ?? null;
            $newKlaster  = $data['klaster_id'] ?? null;
            $assignmentChanged = (int) ($current?->jabatan_id) !== (int) ($newJabatan ?: 0)
                || (int) ($current?->status_pegawai_id) !== (int) ($newStatus ?: 0)
                || (int) ($current?->klaster_id) !== (int) ($newKlaster ?: 0);

            if ($assignmentChanged) {
                $current?->update(['tanggal_selesai' => today()]);
                EmployeeAssignment::create([
                    'employee_id'       => $employee->id,
                    'jabatan_id'        => $newJabatan ?: null,
                    'status_pegawai_id' => $newStatus ?: null,
                    'klaster'           => $newKlaster ?: null,
                    'tanggal_mulai'     => today(),
                ]);
            }

            return $employee->fresh();
        });
    }
}
