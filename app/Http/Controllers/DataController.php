<?php

namespace App\Http\Controllers;

use App\Models\Kabupaten;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponse;
use App\Models\Desa;
use App\Models\Kecamatan;
use Exception;

class DataController extends Controller
{
    use ApiResponse;
    public function getAllKabupaten()
    {
        try {
            $kabupaten = Kabupaten::select('id', 'name')->get();
            return $this->successResponseWithData($kabupaten, 'Data kabupaten berhasil diambil');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function getAllKecamatan(Request $request)
    {
        try {
            $kecamatanQuery = Kecamatan::orderBy('regency_id', 'asc');

            if ($kabQuery = $request->query('kabupaten_id')) {
                $kecamatanQuery->where('regency_id', $kabQuery);
            }
            $data = $kecamatanQuery->get();
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex($data, $data->items(), 'Data kecamatan berhasil diambil');
            } else {
                return $this->errorResponse('Data tidak ditemukan', 404);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function getAllDesa(Request $request)
    {
        try {
            $desaQuery = Desa::orderBy('district_id', 'asc');

            if ($kecQuery = $request->query('kecamatan_id')) {
                $desaQuery->where('district_id', $kecQuery);
            }
            $data = $desaQuery->get();
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex($data, $data->items(), 'Data desa berhasil diambil');
            } else {
                return $this->errorResponse('Data tidak ditemukan', 404);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
