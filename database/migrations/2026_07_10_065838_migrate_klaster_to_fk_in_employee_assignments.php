<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Old enum values → display names
    private array $map = [
        'klaster_1'      => 'Klaster 1',
        'klaster_2'      => 'Klaster 2',
        'klaster_3'      => 'Klaster 3',
        'klaster_4'      => 'Klaster 4',
        'lintas_klaster' => 'Lintas Klaster',
    ];

    public function up(): void
    {
        // Seed klasters from existing enum values
        foreach ($this->map as $slug => $nama) {
            DB::table('klasters')->insert([
                'nama_klaster' => $nama,
                'aktif'        => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        // Add klaster_id FK column
        Schema::table('employee_assignments', function (Blueprint $table) {
            $table->foreignId('klaster_id')->nullable()->after('status_pegawai_id')
                  ->constrained('klasters')->nullOnDelete();
        });

        // Migrate existing enum data to klaster_id
        foreach ($this->map as $slug => $nama) {
            $klaster = DB::table('klasters')->where('nama_klaster', $nama)->first();
            if ($klaster) {
                DB::table('employee_assignments')
                    ->where('klaster', $slug)
                    ->update(['klaster_id' => $klaster->id]);
            }
        }

        // Drop old klaster enum column
        Schema::table('employee_assignments', function (Blueprint $table) {
            $table->dropColumn('klaster');
        });
    }

    public function down(): void
    {
        Schema::table('employee_assignments', function (Blueprint $table) {
            $table->enum('klaster', ['klaster_1', 'klaster_2', 'klaster_3', 'klaster_4', 'lintas_klaster'])
                  ->nullable()->after('status_pegawai_id');
        });
        Schema::table('employee_assignments', function (Blueprint $table) {
            $table->dropForeign(['klaster_id']);
            $table->dropColumn('klaster_id');
        });
    }
};
