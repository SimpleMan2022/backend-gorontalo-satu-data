<?php

namespace App\Http\Controllers;

use App\Http\Traits\ApiResponse;
use App\Models\OpdKabupaten;
use Exception;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Resources\OpdKabupatenResource;
use Illuminate\Http\Request;

class OpdKabupatenController extends Controller
{
    use ApiResponse;
    public function index(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 10;

            $opdQuery = OpdKabupaten::orderBy('created_at', 'desc');

            if ($kab_id = $request->query('kabupaten_id')) {
                $opdQuery->where('kabupaten_id', $kab_id);
            }

            if ($opdProvId = $request->query('opd_provinsi_id')) {
                $opdQuery->where('opd_provinsi_id', $opdProvId);
            }

            $data = $opdQuery->paginate($perPage);
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    OpdKabupatenResource::collection($data),
                    'Data OPD Kabupaten berhasil diambil',
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
                'lokasi' => 'required',
                'kabupaten_id' => 'required',
                'opd_provinsi_id' => 'required'
            ]);

            // Mengubah menjadi huruf kecil
            OpdKabupaten::create($validated);
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
            $data = OpdKabupaten::find($id);

            // Mengecek apabila terdapat data dengan id yang sama
            if ($data) {
                return $this->successResponseWithData(
                    OpdkabupatenResource::make($data),
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
                'kabupaten_id' => 'required',
                'opd_provinsi_id' => 'required'
            ]);

            $opd_kabupaten = OpdKabupaten::find($id);
            $opd_kabupaten->update($validated);

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
            $opd_kabupaten = OpdKabupaten::find($id);
            $opd_kabupaten->delete();
            return $this->successResponse('successfully deleted data', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
