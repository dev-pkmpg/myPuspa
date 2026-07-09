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
}
