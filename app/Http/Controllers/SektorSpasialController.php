<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Traits\ApiResponse;
use App\Models\{SektorSpasial, Sektor};
use App\Http\Resources\SektorSpasialResource;
use Exception;
use Illuminate\Http\Response;

class SektorSpasialController extends Controller
{
    use ApiResponse;
    public function index(Request $request) {

        try {
            // Memanggil data dan request PerPage
            $per_page = $request->per_page ?? 10;
            $variabelQuery = SektorSpasial::orderBy('created_at', 'desc');

            if($sektorId = $request->query('sektor_id')){
                $variabelQuery->where('sektor_id', $sektorId);
            }

            $data = $variabelQuery->paginate($per_page);

            // Mengecek apabila terdapat data
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    SektorSpasialResource::collection($data),
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

    public function store(Request $request) {
        try {
            $req = $request->validate([
                'sektor_id' => 'required|exists:sektor,id',
                'nama' => 'required',
            ]);

            SektorSpasial::create($req);

            return $this->successResponse('Data berhasil dibuat', Response::HTTP_CREATED);
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
            $data = SektorSpasial::find($id);

            // Mengecek apabila terdapat data dengan id yang sama
            if ($data) {
                return $this->successResponseWithData(
                    SektorSpasialResource::make($data),
                    'Data Sektor Spasial berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id) {
        try {
            $req = $request->validate([
                'sektor_id' => 'required|exists:sektor,id',
                'nama' => 'required',
            ]);

            $data = SektorSpasial::find($id);

            if ($data) {
                $data->update($req);

                return $this->successResponse('Data berhasil diubah', Response::HTTP_CREATED);
            } else {
                return $this->errorResponse(
                    'Data tidak ditemukan',
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
            
            return $this->successResponse('Data berhasil dibuat', Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal dibuat', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            SektorSpasial::findOrFail($id)->delete;
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

            $sektor_spasial = SektorSpasial::whereIn('id', $ids)->pluck('id')->toArray();

            $notFoundIds = array_diff($ids, $sektor_spasial);

            if (!empty($notFoundIds)) {
                return $this->errorResponse('Data dengan ID berikut tidak ditemukan: ' . implode(', ', $notFoundIds), Response::HTTP_NOT_FOUND);
            }

            SektorSpasial::whereIn('id', $sektor_spasial)->delete();

            return $this->successResponse('Data sektor spasial berhasil dihapus', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
