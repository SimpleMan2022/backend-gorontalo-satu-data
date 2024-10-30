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
            $per_page = $request->per_page ?? 10;
            $kecamatanQuery = Kecamatan::orderBy('regency_id', 'asc');

            if ($kabQuery = $request->query('kabupaten_id')) {
                $kecamatanQuery->where('regency_id', $kabQuery);
            }
            $data = $kecamatanQuery->paginate($per_page);
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
            $per_page = $request->per_page ?? 10;
            $desaQuery = Desa::orderBy('district_id', 'asc');

            if ($kecQuery = $request->query('kecamatan_id')) {
                $desaQuery->where('district_id', $kecQuery);
            }
            $data = $desaQuery->paginate($per_page);
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
