<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Models\Sektor;
use App\Http\Resources\SektorResource;
use App\Http\Traits\ApiResponse;
use Exception;
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
                    'success',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse(
                    'Data masih kosong',
                    Response::HTTP_INTERNAL_SERVER_ERROR
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
            $req = $request->validate([
                'nama' => 'required'
            ]);

            $sektor = Sektor::create($req);
            return $this->successResponseWithData($sektor, 'Data berhasil dibuat', Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            // Handle validation errors
            return $this->errorResponse($e->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
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
                    'success',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse(
                    'Data tidak ditemukan',
                    Response::HTTP_INTERNAL_SERVER_ERROR
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
            $req = $request->validate([
                'nama' => 'required'
            ]);

            $data = Sektor::find($sektor);

            if($data) {
                $data->update($req);
                return $this->successResponseWithData(
                    SektorResource::make($data),
                    'success',
                    Response::HTTP_OK
                );
            }else {
                return $this->errorResponse(
                    'Data tidak ditemukan',
                    Response::HTTP_INTERNAL_SERVER_ERROR
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

    public function destroy(Sektor $sektor)
    {
        try {

            // Menghapus data sektor sesuai request $sektor
            $data = Sektor::find($sektor);

            if ($data) {
                $data->delete();
            } else {
                return $this->errorResponse(
                    'Data tidak ditemukan',
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            return $this->successResponse('successfully deleted data', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function multiDestroy() {
        try {
            // Menghapus data sektor sesuai request $sektor
            $id = request('id');
            $ids = explode(",", $id);
            $sekor = Sektor::whereIn('id', $ids)->delete();

            return $this->successResponse('Data berhasil dihapus', Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
