<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
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

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['jabatan_id']);
            $table->dropForeign(['status_pegawai_id']);
            $table->dropColumn(['jabatan_id', 'status_pegawai_id', 'klaster']);
        });
    }
};
