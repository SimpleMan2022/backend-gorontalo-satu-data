<?php

namespace App\Http\Controllers;

use App\Http\Traits\ApiResponse;
use App\Models\OpdProvinsi;
use Exception;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Resources\OpdProvinsiResource;

use Illuminate\Http\Request;

class OpdProvinsiController extends Controller
{
    use ApiResponse;
    public function index()
    {
        try {
            $perPage = $request->per_page ?? 10;
            $data = OpdProvinsi::orderBy('created_at', 'desc')->paginate($perPage);
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data, 
                    OpdProvinsiResource::collection($data),
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



    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nama' => 'required',
                'lokasi' => 'required'
            ]);

            OpdProvinsi::create($validated);
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
            $data = OpdProvinsi::find($id);

            // Mengecek apabila terdapat data dengan id yang sama
            if ($data) {
                return $this->successResponseWithData(
                    OpdProvinsiResource::make($data),
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

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'nama' => 'required',
                'lokasi' => 'required',
            ]);

            $opd_provinsi = OpdProvinsi::find($id);
            $opd_provinsi->update($validated);
            
            return $this->successResponse('Data berhasil diperbarui', Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal dibuat', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(string $id)
    {
        try {

            // Menghapus data sektor sesuai request $sektor
            $opd_provinsi = OpdProvinsi::find($id);
            $opd_provinsi->delete();
            return $this->successResponse('Data berhasil dihapus', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
