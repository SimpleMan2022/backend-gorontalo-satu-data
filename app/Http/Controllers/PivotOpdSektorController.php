<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OpdProvinsi;
use App\Models\Sektor;
use App\Models\PivotOpdSektor;
use App\Http\Traits\ApiResponse;
use Exception;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Resources\PivotOpdSektorResource;

class PivotOpdSektorController extends Controller
{
    use ApiResponse;

    public function index(Request $request) {
        try {
            $perPage = $request->per_page ?? 20;
            $PivotOpdSektorQuery = PivotOpdSektor::orderBy('created_at', 'desc');

            if ($opdProvinsiId = $request->query('opd_provinsi_id')) {
                $PivotOpdSektorQuery->where('opd_provinsi_id', $opdProvinsiId);
            }

            if ($opdKabupatenId = $request->query('opd_kabupaten_id')) {
                $PivotOpdSektorQuery->where('opd_kabupaten_id', $opdKabupatenId);
            }

            if ($sektorId = $request->query('sektor_id')) {
                $PivotOpdSektorQuery->where('sektor_id', $sektorId);
            }
            $data = $PivotOpdSektorQuery->paginate($perPage);
            
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data, 
                    PivotOpdSektorResource::collection($data),
                    'success', 
                    Response::HTTP_OK
                );
            } else {
                return response()->json([
                    'message' => 'Data masih kosong.'
                ]);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function byOpd(Request $request, $id) {
        try {
            $perPage = $request->per_page ?? 20;
            $data = PivotOpdSektor::where('opd_provinsi_id', $id)->orderBy('created_at', 'desc')->paginate($perPage);
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data, 
                    PivotOpdSektorResource::collection($data),
                    'success', 
                    Response::HTTP_OK
                );
            } else {
                return response()->json([
                    'message' => 'Data masih kosong.'
                ]);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function bySektor(Request $request, $id) {
        try {
            $perPage = $request->per_page ?? 20;
            $data = PivotOpdSektor::where('sektor_id', $id)->orderBy('created_at', 'desc')->paginate($perPage);
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data, 
                    PivotOpdSektorResource::collection($data),
                    'success', 
                    Response::HTTP_OK
                );
            } else {
                return response()->json([
                    'message' => 'Data masih kosong.'
                ]);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request) {
        try {
            $validated = $request->validate([
                'opd_provinsi_id' => 'required',
                'sektor_id' => 'required'
            ]);

            PivotOpdSektor::create($validated);
            return $this->successResponse('Data berhasil dibuat', Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal dibuat', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(string $id)
    {
        try {
            // Mencari data sektor dengan id
            $data = PivotOpdSektor::find($id);

            // Mengecek apabila terdapat data dengan id yang sama
            if ($data) {
                return $this->successResponseWithData(
                    PivotOpdSektorResource::make($data),
                    'success',
                    Response::HTTP_OK
                );
            } else {
                return response()->json([
                    'message' => 'Data tidak ditemukan.'
                ]);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'opd_provinsi_id' => 'required',
                'sektor_id' => 'required'
            ]);

            $pivot = PivotOpdSektor::find($id);
            $pivot->update($validated);
            return $this->successResponse('Data berhasil diperbarui', Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal diperbarui', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(string $id)
    {
        try {

            // Menghapus data sektor sesuai request $sektor
            $pivot = PivotOpdSektor::find($id);
            $pivot->delete();
            return $this->successResponse('Data berhasil dihapus', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function multiDestroy()
    {
        try {
            $id = request('id');
            $ids = explode(",", $id);

            $pivot = PivotOpdSektor::whereIn('id', $ids)->pluck('id')->toArray();

            $notFoundIds = array_diff($ids, $pivot);

            if (!empty($notFoundIds)) {
                return $this->errorResponse('Data dengan ID berikut tidak ditemukan: ' . implode(', ', $notFoundIds), Response::HTTP_NOT_FOUND);
            }

            PivotOpdSektor::whereIn('id', $pivot)->delete();

            return $this->successResponse('Data pivot Opd sektor berhasil dihapus', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
