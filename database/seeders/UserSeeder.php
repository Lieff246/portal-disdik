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
                'name'              => 'Admin Provinsi',
                'email'             => 'admin@disdik-sulteng.go.id',
                'password'          => Hash::make('password'),
                'cabang_dinas_id'   => null,
                'kode_kabupaten'    => null,
                'role'              => 'admin_provinsi',
            ],
        ];

        foreach ($users as $data) {
            $role = $data['role'];
            unset($data['role']);

            $user = User::firstOrCreate(['email' => $data['email']], $data);
            $user->assignRole($role);
        }
    }
}
