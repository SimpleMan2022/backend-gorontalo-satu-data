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
