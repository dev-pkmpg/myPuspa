<?php

namespace Tests\Feature\Services;

use App\Models\Employee;
use App\Models\User;
use App\Services\EmployeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeServiceTest extends TestCase
{
    use RefreshDatabase;

    private EmployeeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EmployeeService();
    }

    public function test_create_makes_user_and_employee_in_transaction(): void
    {
        $employee = $this->service->create([
            'nama_lengkap' => 'Budi Santoso',
            'email'        => 'budi@example.com',
            'password'     => 'password',
            'nip'          => '1234567890',
            'tanggal_masuk' => '2024-01-01',
        ]);

        $this->assertInstanceOf(Employee::class, $employee);
        $this->assertDatabaseHas('employees', ['nip' => '1234567890']);
        $this->assertDatabaseHas('users', ['email' => 'budi@example.com', 'role' => 'pegawai']);
        $this->assertEquals('budi@example.com', $employee->user->email);
    }

    public function test_create_rolls_back_user_on_employee_failure(): void
    {
        // Seed an existing employee to create a duplicate NIP conflict.
        // Using direct model creation to avoid needing an EmployeeFactory.
        $existingUser = User::create([
            'name'     => 'Existing User',
            'email'    => 'existing@example.com',
            'password' => 'secret',
            'role'     => 'pegawai',
        ]);
        Employee::create([
            'user_id'       => $existingUser->id,
            'nip'           => 'DUPLICATE-NIP',
            'nama_lengkap'  => 'Existing User',
            'tanggal_masuk' => '2023-01-01',
        ]);

        try {
            $this->service->create([
                'nama_lengkap'  => 'Budi Santoso',
                'email'         => 'budi2@example.com',
                'password'      => 'password',
                'nip'           => 'DUPLICATE-NIP', // unique constraint violation
                'tanggal_masuk' => '2024-01-01',
            ]);
            $this->fail('Expected QueryException was not thrown.');
        } catch (\Illuminate\Database\QueryException $e) {
            // Transaction should have rolled back the User insert
            $this->assertDatabaseMissing('users', ['email' => 'budi2@example.com']);
        }
    }
}
