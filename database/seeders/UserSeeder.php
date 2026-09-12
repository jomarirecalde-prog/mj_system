<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'System Admin',
                'full_name' => 'System Administrator',
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'email' => 'admin@inventory.local',
                'employee_id' => 'EMP-001',
                'department' => 'Information Technology',
                'position' => 'System Administrator',
                'phone' => '+63 912 000 0001',
                'date_hired' => '2022-01-15',
                'role' => 'admin',
                'status' => 'active',
                'password' => 'password',
            ],
            [
                'name' => 'Inventory Staff',
                'full_name' => 'Inventory Staff User',
                'first_name' => 'Inventory',
                'last_name' => 'Staff',
                'email' => 'staff@inventory.local',
                'employee_id' => 'EMP-002',
                'department' => 'Operations',
                'position' => 'Inventory Staff',
                'phone' => '+63 912 000 0002',
                'date_hired' => '2023-03-01',
                'role' => 'staff',
                'status' => 'active',
                'password' => 'password',
            ],
            [
                'name' => 'Portal Employee',
                'full_name' => 'Sample Employee User',
                'first_name' => 'Sample',
                'middle_name' => 'A',
                'last_name' => 'Employee',
                'email' => 'employee@inventory.local',
                'employee_id' => 'EMP-2026-000010',
                'department' => 'Human Resources',
                'position' => 'Office Staff',
                'phone' => '+63 917 100 0010',
                'date_hired' => '2024-06-01',
                'role' => 'employee',
                'status' => 'active',
                'password' => 'password',
            ],
        ];

        foreach ($users as $userData) {
            $this->upsertUser($userData);
        }

        foreach ($this->sampleEmployees() as $index => $person) {
            $sequence = $index + 1;
            $employeeId = sprintf('EMP-%04d', 100 + $sequence);
            $email = $this->uniqueEmail($person['first_name'], $person['last_name'], $employeeId);
            $fullName = User::composeFullName($person['first_name'], $person['middle_name'] ?? null, $person['last_name']);

            $this->upsertUser([
                'name' => $fullName,
                'full_name' => $fullName,
                'first_name' => $person['first_name'],
                'middle_name' => $person['middle_name'] ?? null,
                'last_name' => $person['last_name'],
                'email' => $email,
                'employee_id' => $employeeId,
                'department' => $person['department'],
                'position' => $person['position'],
                'phone' => sprintf('+63 9%02d %03d %04d', ($sequence % 80) + 10, 100 + $sequence, 2000 + $sequence),
                'date_hired' => $person['date_hired'],
                'role' => 'employee',
                'status' => 'active',
                'password' => 'password',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $userData
     */
    protected function upsertUser(array $userData): void
    {
        $password = $userData['password'];
        unset($userData['password']);

        User::query()->updateOrCreate(
            ['email' => $userData['email']],
            array_merge($userData, ['password' => Hash::make($password)]),
        );
    }

    protected function uniqueEmail(string $firstName, string $lastName, string $employeeId): string
    {
        $base = Str::lower(Str::slug($firstName.'.'.$lastName, '.'));
        if ($base === '') {
            $base = Str::lower(preg_replace('/[^A-Za-z0-9]+/', '', $employeeId) ?: 'employee');
        }

        return $base.'@employees.local';
    }

    /**
     * 50 sample employees used for DTR demo data.
     *
     * @return list<array{first_name: string, middle_name?: string, last_name: string, department: string, position: string, date_hired: string}>
     */
    protected function sampleEmployees(): array
    {
        return [
            ['first_name' => 'Maria', 'middle_name' => 'Santos', 'last_name' => 'Reyes', 'department' => 'Human Resources', 'position' => 'HR Officer', 'date_hired' => '2021-02-08'],
            ['first_name' => 'Juan', 'middle_name' => 'Cruz', 'last_name' => 'Dela Cruz', 'department' => 'Operations', 'position' => 'Warehouse Supervisor', 'date_hired' => '2020-11-16'],
            ['first_name' => 'Ana', 'middle_name' => 'Lopez', 'last_name' => 'Garcia', 'department' => 'Finance', 'position' => 'Accountant', 'date_hired' => '2022-04-04'],
            ['first_name' => 'Carlos', 'middle_name' => 'Ramos', 'last_name' => 'Mendoza', 'department' => 'Information Technology', 'position' => 'IT Support Specialist', 'date_hired' => '2023-01-09'],
            ['first_name' => 'Liza', 'middle_name' => 'Torres', 'last_name' => 'Villanueva', 'department' => 'Human Resources', 'position' => 'Recruitment Officer', 'date_hired' => '2022-08-22'],
            ['first_name' => 'Pedro', 'middle_name' => 'Bautista', 'last_name' => 'Santos', 'department' => 'Operations', 'position' => 'Inventory Clerk', 'date_hired' => '2021-06-14'],
            ['first_name' => 'Rosa', 'middle_name' => 'Navarro', 'last_name' => 'Fernandez', 'department' => 'Finance', 'position' => 'Payroll Clerk', 'date_hired' => '2020-09-01'],
            ['first_name' => 'Miguel', 'middle_name' => 'Ortiz', 'last_name' => 'Castillo', 'department' => 'Information Technology', 'position' => 'Network Administrator', 'date_hired' => '2021-12-06'],
            ['first_name' => 'Sofia', 'middle_name' => 'Perez', 'last_name' => 'Domingo', 'department' => 'Operations', 'position' => 'Logistics Officer', 'date_hired' => '2023-03-20'],
            ['first_name' => 'Jose', 'middle_name' => 'Ramos', 'last_name' => 'Aquino', 'department' => 'Operations', 'position' => 'Driver', 'date_hired' => '2019-07-15'],
            ['first_name' => 'Elena', 'middle_name' => 'Cruz', 'last_name' => 'Santiago', 'department' => 'Human Resources', 'position' => 'HR Assistant', 'date_hired' => '2024-01-08'],
            ['first_name' => 'Ramon', 'middle_name' => 'Diaz', 'last_name' => 'Gonzales', 'department' => 'Finance', 'position' => 'Bookkeeper', 'date_hired' => '2022-05-30'],
            ['first_name' => 'Patricia', 'middle_name' => 'Gomez', 'last_name' => 'Rivera', 'department' => 'Information Technology', 'position' => 'Software Developer', 'date_hired' => '2023-07-11'],
            ['first_name' => 'Andres', 'middle_name' => 'Flores', 'last_name' => 'Morales', 'department' => 'Operations', 'position' => 'Warehouse Staff', 'date_hired' => '2021-10-18'],
            ['first_name' => 'Carmen', 'middle_name' => 'Ilagan', 'last_name' => 'Bautista', 'department' => 'Finance', 'position' => 'Cashier', 'date_hired' => '2020-03-02'],
            ['first_name' => 'Francis', 'middle_name' => 'Lim', 'last_name' => 'Tan', 'department' => 'Information Technology', 'position' => 'Systems Analyst', 'date_hired' => '2022-11-21'],
            ['first_name' => 'Bea', 'middle_name' => 'Alonzo', 'last_name' => 'Marquez', 'department' => 'Human Resources', 'position' => 'Training Coordinator', 'date_hired' => '2023-09-04'],
            ['first_name' => 'Diego', 'middle_name' => 'Salazar', 'last_name' => 'Pascual', 'department' => 'Operations', 'position' => 'Purchasing Assistant', 'date_hired' => '2021-04-26'],
            ['first_name' => 'Isabel', 'middle_name' => 'Manalo', 'last_name' => 'Cortez', 'department' => 'Finance', 'position' => 'Collections Officer', 'date_hired' => '2022-02-14'],
            ['first_name' => 'Paolo', 'middle_name' => 'Villar', 'last_name' => 'Navarro', 'department' => 'Information Technology', 'position' => 'Helpdesk Technician', 'date_hired' => '2024-03-12'],
            ['first_name' => 'Katrina', 'middle_name' => 'Yap', 'last_name' => 'Ocampo', 'department' => 'Operations', 'position' => 'Quality Inspector', 'date_hired' => '2020-08-10'],
            ['first_name' => 'Luis', 'middle_name' => 'Mercado', 'last_name' => 'Aguilar', 'department' => 'Operations', 'position' => 'Forklift Operator', 'date_hired' => '2019-12-02'],
            ['first_name' => 'Camille', 'middle_name' => 'Sy', 'last_name' => 'Chua', 'department' => 'Finance', 'position' => 'Budget Analyst', 'date_hired' => '2023-05-15'],
            ['first_name' => 'Rafael', 'middle_name' => 'Torres', 'last_name' => 'Del Rosario', 'department' => 'Information Technology', 'position' => 'Database Administrator', 'date_hired' => '2021-09-27'],
            ['first_name' => 'Andrea', 'middle_name' => 'Pineda', 'last_name' => 'Salazar', 'department' => 'Human Resources', 'position' => 'Benefits Officer', 'date_hired' => '2022-07-05'],
            ['first_name' => 'Noel', 'middle_name' => 'Cruz', 'last_name' => 'Padilla', 'department' => 'Operations', 'position' => 'Receiving Clerk', 'date_hired' => '2020-05-19'],
            ['first_name' => 'Gabrielle', 'middle_name' => 'Santos', 'last_name' => 'Lim', 'department' => 'Finance', 'position' => 'Auditor', 'date_hired' => '2023-10-02'],
            ['first_name' => 'Victor', 'middle_name' => 'Reyes', 'last_name' => 'Castro', 'department' => 'Information Technology', 'position' => 'Security Analyst', 'date_hired' => '2022-06-13'],
            ['first_name' => 'Hannah', 'middle_name' => 'Go', 'last_name' => 'Tan', 'department' => 'Human Resources', 'position' => 'HR Records Clerk', 'date_hired' => '2024-02-19'],
            ['first_name' => 'Marco', 'middle_name' => 'Dizon', 'last_name' => 'Villanueva', 'department' => 'Operations', 'position' => 'Dispatch Coordinator', 'date_hired' => '2021-01-25'],
            ['first_name' => 'Jasmine', 'middle_name' => 'Umbay', 'last_name' => 'Ramos', 'department' => 'Finance', 'position' => 'Accounts Payable Clerk', 'date_hired' => '2020-10-12'],
            ['first_name' => 'Benjamin', 'middle_name' => 'Co', 'last_name' => 'Sy', 'department' => 'Information Technology', 'position' => 'Web Developer', 'date_hired' => '2023-08-28'],
            ['first_name' => 'Nicole', 'middle_name' => 'Aguirre', 'last_name' => 'Flores', 'department' => 'Operations', 'position' => 'Inventory Analyst', 'date_hired' => '2022-09-06'],
            ['first_name' => 'Eduardo', 'middle_name' => 'Pangilinan', 'last_name' => 'Mercado', 'department' => 'Operations', 'position' => 'Maintenance Technician', 'date_hired' => '2018-04-23'],
            ['first_name' => 'Angela', 'middle_name' => 'Bautista', 'last_name' => 'Cruz', 'department' => 'Human Resources', 'position' => 'Timekeeping Officer', 'date_hired' => '2021-08-09'],
            ['first_name' => 'Christian', 'middle_name' => 'Ong', 'last_name' => 'Go', 'department' => 'Finance', 'position' => 'Treasury Assistant', 'date_hired' => '2023-02-27'],
            ['first_name' => 'Melissa', 'middle_name' => 'David', 'last_name' => 'Perez', 'department' => 'Information Technology', 'position' => 'QA Tester', 'date_hired' => '2024-04-01'],
            ['first_name' => 'Arnold', 'middle_name' => 'Sison', 'last_name' => 'Lopez', 'department' => 'Operations', 'position' => 'Safety Officer', 'date_hired' => '2019-05-06'],
            ['first_name' => 'Trisha', 'middle_name' => 'Manaloto', 'last_name' => 'Gomez', 'department' => 'Human Resources', 'position' => 'Employee Relations Officer', 'date_hired' => '2022-12-12'],
            ['first_name' => 'Kenneth', 'middle_name' => 'Yu', 'last_name' => 'Chavez', 'department' => 'Finance', 'position' => 'Accounts Receivable Clerk', 'date_hired' => '2021-03-29'],
            ['first_name' => 'Dianne', 'middle_name' => 'Rosales', 'last_name' => 'Torres', 'department' => 'Operations', 'position' => 'Stock Controller', 'date_hired' => '2020-01-13'],
            ['first_name' => 'Gerald', 'middle_name' => 'Natividad', 'last_name' => 'Diaz', 'department' => 'Information Technology', 'position' => 'IT Intern Supervisor', 'date_hired' => '2023-06-05'],
            ['first_name' => 'Alyssa', 'middle_name' => 'Cunanan', 'last_name' => 'Padua', 'department' => 'Finance', 'position' => 'Billing Clerk', 'date_hired' => '2022-10-17'],
            ['first_name' => 'Ryan', 'middle_name' => 'Espiritu', 'last_name' => 'Santos', 'department' => 'Operations', 'position' => 'Delivery Coordinator', 'date_hired' => '2021-07-20'],
            ['first_name' => 'Joanna', 'middle_name' => 'Valdez', 'last_name' => 'Mendoza', 'department' => 'Human Resources', 'position' => 'Onboarding Specialist', 'date_hired' => '2024-05-06'],
            ['first_name' => 'Patrick', 'middle_name' => 'Lazaro', 'last_name' => 'Villamor', 'department' => 'Information Technology', 'position' => 'Desktop Support', 'date_hired' => '2020-06-08'],
            ['first_name' => 'Stephanie', 'middle_name' => 'Quiambao', 'last_name' => 'Reyes', 'department' => 'Operations', 'position' => 'Document Controller', 'date_hired' => '2023-11-13'],
            ['first_name' => 'Daniel', 'middle_name' => 'Abad', 'last_name' => 'Francisco', 'department' => 'Finance', 'position' => 'Tax Assistant', 'date_hired' => '2021-05-03'],
            ['first_name' => 'Karen', 'middle_name' => 'Sarmiento', 'last_name' => 'Dela Rosa', 'department' => 'Operations', 'position' => 'Customer Service Associate', 'date_hired' => '2022-01-24'],
            ['first_name' => 'Jerome', 'middle_name' => 'Castillo', 'last_name' => 'Aguirre', 'department' => 'Information Technology', 'position' => 'DevOps Engineer', 'date_hired' => '2023-04-18'],
        ];
    }
}
