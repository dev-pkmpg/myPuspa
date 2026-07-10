<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('nik', 16)->nullable()->unique()->after('nrk');
            $table->string('npwp', 20)->nullable()->after('nik');
            $table->string('nomor_bpjs_ketenagakerjaan', 20)->nullable()->after('npwp');
            $table->string('nomor_bpjs_kesehatan', 20)->nullable()->after('nomor_bpjs_ketenagakerjaan');
            $table->string('id_sip')->nullable()->after('nomor_bpjs_kesehatan');
            $table->string('id_str')->nullable()->after('id_sip');
            $table->string('nomor_hp', 20)->nullable()->after('id_str');
            $table->string('email_pribadi')->nullable()->after('nomor_hp');
            $table->enum('status_pernikahan', ['belum_menikah', 'menikah', 'cerai_hidup', 'cerai_mati'])->nullable()->after('email_pribadi');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['nik']);
            $table->dropColumn([
                'nik', 'npwp', 'nomor_bpjs_ketenagakerjaan', 'nomor_bpjs_kesehatan',
                'id_sip', 'id_str', 'nomor_hp', 'email_pribadi', 'status_pernikahan',
            ]);
        });
    }
};
