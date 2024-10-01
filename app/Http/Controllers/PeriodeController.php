<?php

namespace App\Http\Controllers;

use App\Http\Resources\PeriodeResource;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponse;
use App\Models\Periode;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class PeriodeController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $per_page = $request->perPage ?? 10;
            $data = Periode::orderBy('created_at', 'desc')->paginate($per_page);

            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    PeriodeResource::collection($data),
                    'success',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse('Data masih kosong.', Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        try {
            $periode = Periode::findOrFail($id);
            return $this->successResponseWithData(new PeriodeResource($periode), 'success', Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nama' => 'required',
                'tanggal_mulai' => 'required|date',
                'tanggal_selesai' => 'required|date',
                'opd_provinsi_id' => 'required|numeric'
            ]);

            Periode::create($validated);
            return $this->successResponse('Data berhasil dibuat', Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal dibuat', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $periode = Periode::findOrFail($id);
            $validated = $request->validate([
                'nama' => 'required',
                'tanggal_mulai' => 'required',
                'tanggal_selesai' => 'required',
                'opd_provinsi_id' => 'required'
            ]);

            $periode->update($validated);
            return $this->successResponse('Data berhasil diubah', Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            $periode = Periode::findOrFail($id);
            $periode->delete();
            return $this->successResponse('Data berhasil dihapus', Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
