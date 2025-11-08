<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'role' => User::USER_ROLE_ADMIN,
            ],
            [
                'name' => 'Manager User',
                'email' => 'manager@example.com',
                'password' => Hash::make('password'),
                'role' => User::USER_ROLE_ADMIN,
            ],
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => Hash::make('password'),
                'role' => User::USER_ROLE_USER,
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'password' => Hash::make('password'),
                'role' => User::USER_ROLE_USER,
            ],
            [
                'name' => 'Bob Wilson',
                'email' => 'bob@example.com',
                'password' => Hash::make('password'),
                'role' => User::USER_ROLE_USER,
            ],
        ];

        User::insert($users);
    }
}
