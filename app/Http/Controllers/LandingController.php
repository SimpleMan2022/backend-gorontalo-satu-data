<?php

namespace App\Http\Controllers;

use App\Http\Resources\IndikatorLandingResource;
use App\Http\Resources\IndikatorResource;
use App\Http\Resources\SektorResource;
use App\Http\Resources\SektorSpasialResource;
use App\Models\Sektor;
use App\Http\Traits\ApiResponse;
use App\Models\AntarWaktu;
use App\Models\Indikator;
use App\Models\OpdProvinsi;
use App\Models\SektorSpasial;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LandingController extends Controller
{
    use ApiResponse;
    public function getSektor(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 10;
            $data = Sektor::orderBy('created_at', 'desc');

            if ($perPage === 'all') {
                $data = $data->get();
            } else {
                $data = $data->paginate($perPage);
            }

            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    $perPage === 'all' ? $data : $data->items(),
                    'Data Sektor berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse('Data masih kosong', Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getAntarWaktu(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 10;
            $data = AntarWaktu::orderBy('created_at', 'desc');

            if ($perPage === 'all') {
                $data = $data->get();
            } else {
                $data = $data->paginate($perPage);
            }

            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    $perPage === 'all' ? $data : $data->items(),
                    'Data Antar Waktu berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse('Data masih kosong', Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getIndikatorAndDataSearch(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 10;
            $data = Indikator::where('status', 'public')->orderBy('created_at', 'desc');
            if ($request->query('sektor_id')) {
                $data = $data->where('sektor_id', $request->sektor_id);
            }

            if ($request->query('opd_provinsi_id')) {
                $data = $data->where('opd_provinsi_id', $request->opd_provinsi_id);
            }
            if ($request->query('search')) {
                $data = $data->where('nama', 'like', '%' . $request->search . '%');
            }

            if ($request->query('antar_waktu')) {
                $data = $data->where('antar_waktu_id', $request->antar_waktu_id);
            }

            if ($perPage === 'all') {
                $data = $data->get();
            } else {
                $data = $data->paginate($perPage);
            }

            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    $perPage === 'all' ? IndikatorLandingResource::collection($data) : IndikatorLandingResource::collection($data->items()),
                    'Data Indikator berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse('Data masih kosong', Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getIndikator(Request $request, $id) 
    {
        try {
            // Mencari data sektor dengan id
            $data = Indikator::with('rekap_hasil.hasil_data')->where('id', $id)->get();

            $perKabupaten = $data->flatMap(function ($indikator) {
                return $indikator->rekap_hasil->flatMap(function ($rekap) {
                    return $rekap->hasil_data->filter(function ($hasil) {
                        return str_contains($hasil->konten, 'Kabupaten'); // Filter untuk konten kabupaten
                    })->pluck('konten'); // Ambil hanya nilai konten
                });
            })->groupBy(function ($kabupaten) {
                return $kabupaten; // Kelompokkan berdasarkan nama kabupaten
            })->map(function ($group) {
                return $group->count(); // Hitung jumlah data per kabupaten
            });

            $data = $data->map(function ($item) use ($perKabupaten) {
                // $item->perkabupaten = $perKabupaten;
                return [
                    'id' => $item->id,
                    'nama_indikator' => $item->nama,
                    'nama_sektor' => $item->sektor->nama,
                    'antar_waktu' => $item->antar_waktu->nama,
                    'opd_provinsi' => $item->opd_provinsi->nama,
                    'logo_opd' => $item->opd_provinsi->logo,
                    'rekap_hasil' => $item->rekap_hasil,
                    'updated_at' => $item->updated_at,
                    'created_at' => $item->created_at,
                    'perkabupaten' => $perKabupaten,
                ];
            });

            // Mengecek apabila terdapat data dengan id yang sama
            if ($data) {
                return $this->successResponseWithData(
                    $data,
                    'Data indikator berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse(
                    'Data tidak ditemukan',
                    Response::HTTP_NOT_FOUND
                );
            }
        }catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getOpdProvinsi(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 10;
            $data = OpdProvinsi::orderBy('created_at', 'desc');

            if ($perPage === 'all') {
                $data = $data->get();
            } else {
                $data = $data->paginate($perPage);
            }

            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    $perPage === 'all' ? $data : $data->items(),
                    'Data OPD Provinsi berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse('Data masih kosong', Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
