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
                'nrk'      => $data['nrk'] ?? null,
                'email'    => $data['email'],
                'password' => $data['password'],
                'role'     => 'pegawai',
            ]);

            $employee = Employee::create([
                'user_id'      => $user->id,
                'nip'          => $data['nip'],
                'nama_lengkap' => $data['nama_lengkap'],
                'status_aktif' => $data['status_aktif'] ?? true,
                'tanggal_masuk' => $data['tanggal_masuk'],
                'nik'                        => $data['nik'] ?? null,
                'npwp'                       => $data['npwp'] ?? null,
                'nomor_bpjs_ketenagakerjaan' => $data['nomor_bpjs_ketenagakerjaan'] ?? null,
                'nomor_bpjs_kesehatan'       => $data['nomor_bpjs_kesehatan'] ?? null,
                'id_sip'                     => $data['id_sip'] ?? null,
                'id_str'                     => $data['id_str'] ?? null,
                'nomor_hp'                   => $data['nomor_hp'] ?? null,
                'email_pribadi'              => $data['email_pribadi'] ?? null,
                'status_pernikahan'          => $data['status_pernikahan'] ?? null,
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
            $userUpdate = [
                'nrk'   => $data['nrk'] ?? null,
                'email' => $data['email'],
            ];
            if (! empty($data['password'])) {
                $userUpdate['password'] = $data['password'];
            }
            $employee->user->update($userUpdate);

            $employee->update([
                'nip'          => $data['nip'],
                'nama_lengkap' => $data['nama_lengkap'],
                'status_aktif' => $data['status_aktif'] ?? true,
                'tanggal_masuk' => $data['tanggal_masuk'],
                'nik'                        => $data['nik'] ?? null,
                'npwp'                       => $data['npwp'] ?? null,
                'nomor_bpjs_ketenagakerjaan' => $data['nomor_bpjs_ketenagakerjaan'] ?? null,
                'nomor_bpjs_kesehatan'       => $data['nomor_bpjs_kesehatan'] ?? null,
                'id_sip'                     => $data['id_sip'] ?? null,
                'id_str'                     => $data['id_str'] ?? null,
                'nomor_hp'                   => $data['nomor_hp'] ?? null,
                'email_pribadi'              => $data['email_pribadi'] ?? null,
                'status_pernikahan'          => $data['status_pernikahan'] ?? null,
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
                    'klaster_id'        => $newKlaster ?: null,
                    'tanggal_mulai'     => today(),
                ]);
            }

            return $employee->fresh();
        });
    }
}
