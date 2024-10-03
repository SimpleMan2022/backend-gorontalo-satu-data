<?php

namespace App\Http\Controllers;

use App\Models\AntarWaktu;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponse;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AntarWaktuController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $data = AntarWaktu::select('id', 'tahun', 'nama')->orderBy('tahun', 'desc')->get();
            if ($data->isNotEmpty()) {
                return $this->successResponseWithData($data, 'success', Response::HTTP_OK);
            } else {
                return response()->json([
                    'message' => 'Data masih kosong.'
                ]);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'tahun' => 'required|numeric|digits:4|unique:antar_waktu',
                'nama' => 'required'
            ]);
            AntarWaktu::create($validated);
            return $this->successResponse('Data berhasil dibuat', Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal dibuat', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    //show??

    public function update(Request $request, $id)
    {
        try {
            $antar_waktu = AntarWaktu::findOrFail($id);
            $validated = $request->validate([
                'tahun' => 'required|numeric|digits:4|unique:antar_waktu,tahun,' . $id,
                'nama' => 'required'
            ]);
            $antar_waktu->update($validated);
            return $this->successResponse('Data berhasil diubah', Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal diubah', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            $antar_waktu = AntarWaktu::findOrFail($id);
            $antar_waktu->delete();
            return $this->successResponse('Data berhasil dihapus', Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function multiDestroy() {
        try {
            // Menghapus data sektor sesuai request $sektor
            $id = request('id');
            $ids = explode(",", $id);
            $antar_waktu = AntarWaktu::whereIn('id', $ids)->delete();

            return $this->successResponse('Data berhasil dihapus', Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
