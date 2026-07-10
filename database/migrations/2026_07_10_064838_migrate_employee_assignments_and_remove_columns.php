<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate existing jabatan/status/klaster data into employee_assignments
        DB::table('employees')
            ->where(function ($q) {
                $q->whereNotNull('jabatan_id')
                  ->orWhereNotNull('status_pegawai_id')
                  ->orWhereNotNull('klaster');
            })
            ->get()
            ->each(function ($employee) {
                DB::table('employee_assignments')->insert([
                    'employee_id'       => $employee->id,
                    'jabatan_id'        => $employee->jabatan_id,
                    'status_pegawai_id' => $employee->status_pegawai_id,
                    'klaster'           => $employee->klaster,
                    'tanggal_mulai'     => $employee->tanggal_masuk,
                    'tanggal_selesai'   => null,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['jabatan_id']);
            $table->dropForeign(['status_pegawai_id']);
            $table->dropColumn(['jabatan_id', 'status_pegawai_id', 'klaster']);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('jabatan_id')->nullable()->after('tanggal_masuk')
                  ->constrained('jabatans')->nullOnDelete();
            $table->foreignId('status_pegawai_id')->nullable()->after('jabatan_id')
                  ->constrained('status_pegawais')->nullOnDelete();
            $table->enum('klaster', ['klaster_1', 'klaster_2', 'klaster_3', 'klaster_4', 'lintas_klaster'])
                  ->nullable()->after('status_pegawai_id');
        });
    }
};
