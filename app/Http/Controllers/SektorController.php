<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ConvertImage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Models\Sektor;
use App\Http\Resources\SektorResource;
use App\Http\Traits\ApiResponse;
use Exception;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;

class SektorController extends Controller
{
    // Memanggil Traits Api Response
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            // Memanggil data dan request PerPage
            $per_page = $request->per_page ?? 10;
            $data = Sektor::orderBy('created_at', 'desc')->paginate($per_page);

            // Mengecek apabila terdapat data
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    SektorResource::collection($data),
                    'Data sektor berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse(
                    'Data masih kosong',
                    Response::HTTP_NOT_FOUND
                );
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            // Membuat validasi
            $validated = $request->validate([
                'nama' => 'required|min:3',
                'logo' => [
                    'required',
                    'url', // Validasi bahwa input adalah URL
                    'regex:/^https?:\/\/.+\.(webp)$/i' // Validasi format .webp di URL
                ]
            ]);

            // Simpan data sektor
            Sektor::create($validated);

            return $this->successResponse(
                'Data sektor berhasil dibuat',
                Response::HTTP_CREATED
            );
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
            $data = Sektor::find($id);

            // Mengecek apabila terdapat data dengan id yang sama
            if ($data) {
                return $this->successResponseWithData(
                    SektorResource::make($data),
                    'Data sektor berhasil diambil',
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

    public function update(Request $request, $sektor)
    {
        try {
            // Membuat Validasi
            $validated = $request->validate([
                'nama' => 'required',
                'logo' => [
                    'nullable',
                    'url', // Validasi bahwa input adalah URL
                    'regex:/^https?:\/\/.+\.(webp)$/i' // Validasi format .webp di URL
                ]
            ]);

            $data = Sektor::find($sektor);

            if ($data) {
                $data->update($validated);
                return $this->successResponse(
                    'Data sektor berhasil diubah',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse(
                    'Data tidak ditemukan',
                    Response::HTTP_NOT_FOUND
                );
            }
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
        } catch (ValidationException $e) {
            // Handle validation errors
            return $this->errorResponse($e->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {

            // Menghapus data sektor sesuai request $sektor
            $data = Sektor::find($id);

            if ($data) {
                $data->delete();
            } else {
                return $this->errorResponse(
                    'Data tidak ditemukan',
                    Response::HTTP_NOT_FOUND
                );
            }

            return $this->successResponse('Data sektor berhasil dihapus', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function multiDestroy()
    {
        try {
            $id = request('id');
            $ids = explode(",", $id);

            $sektors = Sektor::whereIn('id', $ids)->pluck('id')->toArray();

            $notFoundIds = array_diff($ids, $sektors);

            if (!empty($notFoundIds)) {
                return $this->errorResponse('Data dengan ID berikut tidak ditemukan: ' . implode(', ', $notFoundIds), Response::HTTP_NOT_FOUND);
            }

            Sektor::whereIn('id', $sektors)->delete();

            return $this->successResponse('Data sektor berhasil dihapus', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
