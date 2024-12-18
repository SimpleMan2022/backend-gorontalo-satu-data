<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserOpdKabupaten;
use App\Models\UserOpdProvinsi;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\OpdKabupaten;
use App\Models\OpdProvinsi;
use App\Models\Kabupaten;
use App\Models\Enumerator;
use App\Models\EnumeratorKabupaten;
use App\Models\EnumeratorProvinsi;
use Spatie\Permission\PermissionRegistrar;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        //create permissions for admin
        $permissions_admin = [
            'view_indikator',
            'view_subjek',
            'view_variabel',
            'kelola_sektor',
            'kelola_antarwaktu',            
            'kelola_user_opd_provinsi',
            'kelola_user_opd_kabupaten',
            'kelola_opd_kabupaten',
            'kelola_opd_provinsi',
            'kelola_sektor_spasial',
            'view_sektor_spasial',
            'view_olahan_spasial',
            'view_hasil_spasial',
        ];

        // Create each permission
        foreach ($permissions_admin as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Assign these permissions to a specific role (e.g., admin)
        $role1 = Role::findOrCreate('admin');

        // Assign permissions to the role
        $role1->givePermissionTo($permissions_admin);

        $user_admin = User::create([
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $user_admin->assignRole('admin');

        // Create permissions for opd provinsi
        $permissions_provinsi = [
            'kelola_indikator',
            'kelola_subjek',
            'kelola_variabel',
            'kelola_periode',
            'view_sektor',
            'verifikasi_subjek_indikator',
            'verifikasi_enumerator_indikator',
            'verifikasi_indikator',
            'kelola_enumerator',
            'kelola_user_opd_kabupaten',
            'kelola_enumerator_indikator',
            'view_opd_kabupaten',
            'view_sektor_spasial',
            'view_olahan_spasial',
            'view_hasil_spasial',
            'kelola_olahan_spasial',
            'kelola_hasil_spasial',
        ];

        // Create each permission
        foreach ($permissions_provinsi as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Assign these permissions to a specific role (e.g., admin)
        $role2 = Role::findOrCreate('opd_provinsi');

        // Assign permissions to the role
        $role2->givePermissionTo($permissions_provinsi);

        $user_opd_provinsi = User::create([
            'username' => 'dinaskesehatan',
            'email' => 'dinaskesehatan@example.com',
            'password' => Hash::make('gsdmakro321'),
        ]);

        $user_enumerator_provinsi = User::create([
            'username' => 'enumeratordinkes',
            'email' => 'enumeratordinkes@example.com',
            'password' => Hash::make('gsdmakro321'),
        ]);

        $enumerator_provinsi = Enumerator::create([
            'nama' => 'Enumerator Evan',
            'user_id' => $user_enumerator_provinsi->id,
            'jabatan' => 'Operator'
        ]);

        $opd_provinsi =  OpdProvinsi::create([
            'nama' => 'Dinas Kesehatan',
            'lokasi' => 'Jl. Kesehatan No. 1'
        ]);

        $pivot_opdprovinsi_enumerator = EnumeratorProvinsi::create([
            'opd_provinsi_id' => $opd_provinsi->id,
            'enumerator_id' => $enumerator_provinsi->id
        ]);

        $userOpdProv  = [
            'nama' => 'Budi Santoso',
            'opd_provinsi_id' => $opd_provinsi->id,
            'user_id' => $user_opd_provinsi->id,
            'jabatan' => 'Kepala Dinas',
        ];

        UserOpdProvinsi::create($userOpdProv);

        $user_opd_provinsi->assignRole('opd_provinsi');

        // Create permissions for opd kabupaten
        $permissions_kabupaten = [
            'view_indikator',
            'view_subjek',
            'view_variabel',
            'view_subjek',
            'kelola_berita_acara',
            'kelola_enumerator_indikator',
            'verifikasi_enumerator_indikator',
            'kelola_enumerator',
            'kelola_user_opd_kabupaten'            
        ];

        // Create each permission
        foreach ($permissions_kabupaten as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Assign these permissions to a specific role (e.g., admin)
        $role3 = Role::findOrCreate('opd_kabupaten');

        // Assign permissions to the role
        $role3->givePermissionTo($permissions_kabupaten);

        // Create permissions for enumerator
        $permissions_enumerator = [
            'view_hasil_data',
            'view_indikator',
            'view_sektor',
            'view_variabel',
            'kelola_hasil_data',
        ];

        // Create each permission
        foreach ($permissions_enumerator as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Assign these permissions to a specific role (e.g., admin)
        $role4 = Role::findOrCreate('enumerator');

        // Assign permissions to the role
        $role4->givePermissionTo($permissions_enumerator);

        $opd_kabupaten_data = [
            [
                'user' => [
                    'username' => 'dinaskesehatan_bonebolango',
                    'email' => 'dinaskesehatan_bonebolango@example.com',
                    'password' => Hash::make('gsdmakro321'),
                ],
                'opd' => [
                    'nama' => 'Dinas Kesehatan Kabupaten Bone Bolango',
                    'lokasi' => 'KABUPATEN BONE BOLANGO'
                ],
                'userOpd' => [
                    'nama' => 'Budi Santoso',
                    'jabatan' => 'Kepala Dinas'
                ],
                'enumerator_user' => [
                    'username' => 'enumeratordinkes_bonebolango',
                    'email' => 'enumeratordinkes_bonebolango@example.com',
                    'password' => Hash::make('gsdmakro321'),
                ],
                'enumerator' => [
                    'nama' => 'Enumerator Rizki',
                    'jabatan' => 'Operator'
                ]
            ],
            [
                'user' => [
                    'username' => 'dinaskesehatan_kotagorontalo',
                    'email' => 'dinaskesehatan_kotagorontalo@example.com',
                    'password' => Hash::make('gsdmakro321'),
                ],
                'opd' => [
                    'nama' => 'Dinas Kesehatan Kota Gorontalo',
                    'lokasi' => 'KOTA GORONTALO'
                ],
                'userOpd' => [
                    'nama' => 'Andi Wirawan',
                    'jabatan' => 'Sekretaris Dinas'
                ],
                'enumerator_user' => [
                    'username' => 'enumeratordinkes_kotagorontalo',
                    'email' => 'enumeratordinkes_kotagorontalo@example.com',
                    'password' => Hash::make('gsdmakro321'),
                ],
                'enumerator' => [
                    'nama' => 'Enumerator Ikbal',
                    'jabatan' => 'Operator'
                ]
            ],
            [
                'user' => [
                    'username' => 'dinaskesehatan_boalemo',
                    'email' => 'dinaskesehatan_boalemo@example.com',
                    'password' => Hash::make('gsdmakro321'),
                ],
                'opd' => [
                    'nama' => 'Dinas Kesehatan Kabupaten Boalemo',
                    'lokasi' => 'KABUPATEN BOALEMO',

                ],
                'userOpd' => [
                    'nama' => 'Andi Wirawan',
                    'jabatan' => 'Sekretaris Dinas'
                ],
                'enumerator_user' => [
                    'username' => 'enumeratordinkes_boalemo',
                    'email' => 'enumeratordinkes_boalemo@example.com',
                    'password' => Hash::make('gsdmakro321'),
                ],
                'enumerator' => [
                    'nama' => 'Enumerator Aldi',
                    'jabatan' => 'Operator'
                ]
            ]
        ];

        foreach ($opd_kabupaten_data as $data) {
            // 1. Buat user
            $user_opd_kabupaten = User::create($data['user']);
            $user_opd_kabupaten->assignRole('opd_kabupaten');

            $kabupaten = Kabupaten::where('name', $data['opd']['lokasi'])->first();

            if ($kabupaten) {
                // 2. Buat opd kabupaten
                $opd_kabupaten = OpdKabupaten::create([
                    'nama' => $data['opd']['nama'],
                    'lokasi' => $data['opd']['lokasi'],
                    'kabupaten_id' => $kabupaten->id, // kabupaten_id dari tabel regencies
                    'opd_provinsi_id' => $opd_provinsi->id
                ]);

                // 3. Buat user_opd_kabupaten dan menghubungkan user dan opd kabupaten
                UserOpdKabupaten::create([
                    'nama' => $data['userOpd']['nama'],
                    'opd_kabupaten_id' => $opd_kabupaten->id,
                    'user_id' => $user_opd_kabupaten->id,
                    'jabatan' => $data['userOpd']['jabatan']
                ]);

                // 5. Buat user enumerator
                $user_enumerator = User::create($data['enumerator_user']);
                $user_enumerator->assignRole('enumerator');

                // 6. Buat enumerator di tabel user_opd_kabupaten atau tabel lain sesuai kebutuhan
                $enumerator = Enumerator::create([
                    'nama' => $data['enumerator']['nama'],
                    'user_id' => $user_enumerator->id, // Hubungkan dengan user enumerator
                    'jabatan' => $data['enumerator']['jabatan']
                ]);

                $enumerator_kabupaten = EnumeratorKabupaten::create([
                    'opd_kabupaten_id' => $opd_kabupaten->id,
                    'enumerator_id' => $enumerator->id,
                ]);
            }
        }

        $user_enumerator_provinsi->assignRole('enumerator');
    }
}
