<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add nrk to users (idempotent)
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->string('nrk', 20)->nullable()->unique()->after('email');
            });
        } catch (\Throwable) {
            // Column already exists — no-op
        }

        // Copy nrk from employees to users where not yet populated
        DB::statement(
            'UPDATE users SET nrk = (SELECT nrk FROM employees WHERE employees.user_id = users.id) WHERE nrk IS NULL'
        );

        // Drop name from users (idempotent)
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        } catch (\Throwable) {
            // Column doesn't exist — no-op
        }

        // Drop unique index on employees.nrk if it exists, then drop the column
        try {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropUnique(['nrk']);
            });
        } catch (\Throwable) {
            // Index doesn't exist — no-op
        }

        try {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('nrk');
            });
        } catch (\Throwable) {
            // Column doesn't exist — no-op
        }
    }

    public function down(): void
    {
        // Restore nrk on employees with unique index
        try {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('nrk', 20)->nullable()->unique()->after('nip');
            });
            DB::statement(
                'UPDATE employees SET nrk = (SELECT nrk FROM users WHERE users.id = employees.user_id)'
            );
        } catch (\Throwable) {
            // Column already exists — no-op
        }

        // Remove nrk from users
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['nrk']);
            });
        } catch (\Throwable) {
            // Index doesn't exist — no-op
        }

        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('nrk');
            });
        } catch (\Throwable) {
            // Column doesn't exist — no-op
        }

        // Restore name on users
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->string('name')->default('')->after('id');
            });
        } catch (\Throwable) {
            // Column already exists — no-op
        }
    }
};
