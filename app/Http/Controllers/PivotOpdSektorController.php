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

    public function index() {
        try {
            $perPage = $request->per_page ?? 20;
            $data = PivotOpdSektor::orderBy('created_at', 'desc')->paginate($perPage);
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
}
