<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserOpdKabupaten;
use App\Models\UserOpdProvinsi;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
            ],
            [
                'username' => 'user',
                'email' => 'user@example.com',
                'password' => Hash::make('password'),
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }

        $userOpdKab = [
            'nama' => 'Agus Kurniawan',
            'opd_kabupaten_id' => 1,
            'user_id' => 1,
            'jabatan' => 'Admin OPD',
        ];

        $userOpdProv  = [
            'nama' => 'Budi Santoso',
            'opd_provinsi_id' => 1,
            'user_id' => 2,
            'jabatan' => 'Kepala Dinas',
        ];

        UserOpdKabupaten::create($userOpdKab);
        UserOpdProvinsi::create($userOpdProv);
    }
}
