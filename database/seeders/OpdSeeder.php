<?php

namespace Database\Seeders;

use App\Models\OpdKabupaten;
use App\Models\OpdProvinsi;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OpdSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $opd = [
            [
                'nama' => 'Dinas Pendidikan',
                'lokasi' => 'Jl. Pendidikan No. 1'
            ],
            [
                'nama' => 'Dinas Komunikasi dan Informatika',
                'lokasi' => 'Jl. Kesehatan No. 1'
            ],
            [
                'nama' => 'Dinas Perhubungan',
                'lokasi' => 'Jl. Perhubungan No. 1'
            ]
        ];

        foreach ($opd as $data) {
            OpdProvinsi::create($data);
        }

        $opdKab = [
            [
                'nama' => 'Dinas Pendidikan Kabupaten Bone Bolango',
                'lokasi' => 'Jl. Pendidikan No. 12',
                'kabupaten_id' => 7504,
                'opd_provinsi_id' => 1,
            ],
            [
                'nama' => 'Dinas Komunikasi dan Informatika Kabupaten Bone Bolango',
                'lokasi' => 'Jl. Kesehatan No. 12',
                'kabupaten_id' => 7504,
                'opd_provinsi_id' => 2,
            ],
            [
                'nama' => 'Dinas Perhubungan Kabupaten Bone Bolango',
                'lokasi' => 'Jl. Perhubungan No. 12',
                'kabupaten_id' => 7504,
                'opd_provinsi_id' => 3,
            ]
        ];

        foreach ($opdKab as $data) {
            OpdKabupaten::create($data);
        }
    }
}
