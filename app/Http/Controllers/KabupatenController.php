<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Kabupaten, Kecamatan, Desa};
use App\Models\{HasilData, Variabel,Indikator, RekapHasil, Periode, Enumerator, OpdProvinsi, OpdKabupaten, PivotEnumeratorIndikator};
use App\Http\Traits\ApiResponse;
use Exception;

class KabupatenController extends Controller
{
    use ApiResponse;

    public function getDesa(Request $request) {
        try {
            $nama_desa = 'Pantungo';
            $desa = Desa::where('name', $nama_desa)->first();

            $indikator = Indikator::whereHas('rekap_hasil.hasil_data', function ($query) use ($desa) {
                $query->where('konten', $desa->name);
            })->with(['rekap_hasil' => function ($query) use ($desa) {
                $query->whereHas('hasil_data', function ($query) use ($desa) {
                    $query->where('konten', $desa->name);
                });
            }])->get();

            $result = $indikator->map(function ($indikator) {
                return [
                    'nama_indikator' => $indikator->nama, // Ganti sesuai field nama indikator
                    'nama_opd' => $indikator->opd_provinsi->nama, // Ganti sesuai field nama indikator
                    'antar_waktu' => $indikator->antar_waktu->nama . ' - ' . $indikator->antar_waktu->tahun, // Ganti sesuai field nama indikator
                    'rekap_hasil' => $indikator->rekap_hasil->map(function ($rekap_hasil) {
                        return [
                            'hasil_data' => $rekap_hasil->hasil_data->map(function ($hasil_data) {
                                return [
                                    'nama_variabel' => $hasil_data->variabel->nama, // Ganti sesuai field id variabel
                                    'konten' => $hasil_data->konten,
                                ];
                            })->filter()->values(),
                        ];
                    })->filter()->values()                   
                ];
            })->filter()->values();

            return response()->json([
                'nama_desa' => $desa->name,
                'id_desa' => $desa->id,
                'indikator' => $result,
            ]);

            // $result = [
            //     'nama_desa' => $desa->name,
            //     'id_desa' => $desa->id,
            //     'indikator' => $indikator
            // ];

            // return $indikator;
        }catch(Exception $e) {
            return $e;
        }
    }
}
