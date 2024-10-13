<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Indikator, Sektor, AntarWaktu, OpdProvinsi};
use App\Http\Resources\IndikatorResource;
use App\Http\Traits\ApiResponse;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;

class IndikatorController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 10;
            $data = Indikator::orderBy('created_at', 'desc')->paginate($perPage);
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    IndikatorResource::collection($data),
                    'Data indikator berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse('Data masih kosong', Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function bySektor($sektor)
    {
        try {
            $perPage = $request->per_page ?? 10;
            $data = Indikator::where('sektor_id', $sektor)->orderBy('created_at', 'desc')->paginate($perPage);
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    IndikatorResource::collection($data),
                    'Data indikator berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse('Data masih kosong', Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function byAntarWaktu($antar_waktu)
    {
        try {
            $perPage = $request->per_page ?? 10;
            $data = Indikator::where('antar_waktu_id', $antar_waktu)->orderBy('created_at', 'desc')->paginate($perPage);
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    IndikatorResource::collection($data),
                    'Data indikator berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse('Data masih kosong', Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function byOpdProvinsi($opd_provinsi)
    {
        try {
            $perPage = $request->per_page ?? 10;
            $data = Indikator::where('opd_provinsi_id', $opd_provinsi)->orderBy('created_at', 'desc')->paginate($perPage);
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    IndikatorResource::collection($data),
                    'Data indikator berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse('Data masih kosong', Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nama' => 'required',
                'sektor_id' => 'required',
                'antar_waktu_id' => 'required',
                'opd_provinsi_id' => 'required',
                'status' => 'required',
            ]);

            Indikator::create($validated);

            return $this->successResponse('Data indikator berhasil dibuat', Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal dibuat', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        try {
            // Mencari data sektor dengan id
            $data = Indikator::find($id);

            // Mengecek apabila terdapat data dengan id yang sama
            if ($data) {
                return $this->successResponseWithData(
                    IndikatorResource::make($data),
                    'Data indikator berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse(
                    'Data tidak ditemukan',
                    Response::HTTP_NOT_FOUND
                );
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'nama' => 'required',
                'sektor_id' => 'required',
                'antar_waktu_id' => 'required',
                'opd_provinsi_id' => 'required',
                'status' => 'required',
            ]);

            $indikator = Indikator::findOrFail($id);
            $indikator->update($validated);
            return $this->successResponse('Data berhasil diubah', Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal diperbarui', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:public,private,pending',
            ]);

            $indikator = Indikator::findOrFail($id);
            $indikator->update($validated);

            return $this->successResponse('Data berhasil diubah', Response::HTTP_CREATED);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal diperbarui', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            // Menghapus data sektor sesuai request $sektor
            $indikator = Indikator::findOrFail($id);
            $indikator->delete();
            return $this->successResponse('Data berhasil dihapus', Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function multiDestroy()
    {
        try {
            $id = request('id');
            $ids = explode(",", $id);

            $indikator = Indikator::whereIn('id', $ids)->pluck('id')->toArray();

            $notFoundIds = array_diff($ids, $indikator);

            if (!empty($notFoundIds)) {
                return $this->errorResponse('Data dengan ID berikut tidak ditemukan: ' . implode(', ', $notFoundIds), Response::HTTP_NOT_FOUND);
            }

            Indikator::whereIn('id', $indikator)->delete();

            return $this->successResponse('Data indikator berhasil dihapus', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
