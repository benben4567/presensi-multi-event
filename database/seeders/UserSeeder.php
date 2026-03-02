<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin',
                'email' => 'admin@presensi.test',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ],
            [
                'name' => 'Operator',
                'email' => 'operator@presensi.test',
                'password' => Hash::make('password'),
                'role' => 'operator',
            ],
        ];

        foreach ($users as $data) {
            $role = $data['role'];
            unset($data['role']);

            $user = User::firstOrCreate(['email' => $data['email']], $data);
            $user->syncRoles($role);
        }
    }
}
