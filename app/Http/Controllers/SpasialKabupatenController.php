<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{SpasialKabupaten, OlahanSpasial};
use App\Http\Resources\SpasialKabupatenResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\OlahanSpasialResource;
use Exception;
use App\Http\Traits\ApiResponse;

class SpasialKabupatenController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 10;

            $spasialQuery = SpasialKabupaten::orderBy('created_at', 'desc');

            // if ($search = $request->query('search')) {
            //     $spasialQuery->where('tahun', 'like', '%' . $search . '%');
            // }

            if ($olahanSpasialId = $request->query('olahan_spasial_id')) {
                $spasialQuery->where('olahan_spasial_id', $olahanSpasialId);
            }

            if ($kabupatenId = $request->query('kabupaten_id')) {
                $spasialQuery->where('kabupaten_id', $kabupatenId);
            }

            $data = $spasialQuery->paginate($perPage);
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    SpasialKabupatenResource::collection($data),
                    'Data Spasial Spasial berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse('Data masih kosong', Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $req = $request->validate([
                'olahan_spasial_id' => 'required|numeric',
                'kabupaten_id' => 'required|numeric',
                'tahun' => 'required',
                'konten' => 'required',
            ]);

            $data = SpasialKabupaten::create($req);

            return $this->successResponse('Data Spasial berhasil dibuat', Response::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            // Mencari data sektor dengan id
            $data = SpasialKabupaten::find($id);

            // Mengecek apabila terdapat data dengan id yang sama
            if ($data) {
                return $this->successResponseWithData(
                    SpasialKabupatenResource::make($data),
                    'Data Spasial berhasil diambil',
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


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $req = $request->validate([
                'olahan_spasial_id' => 'required|numeric',
                'kabupaten_id' => 'required|numeric',
                'tahun' => 'required',
                'konten' => 'required',
            ]);

            $data = SpasialKabupaten::find($id);

            if ($data) {
                $data->update($req);

                return $this->successResponse('Data berhasil diubah', Response::HTTP_CREATED);
            } else {
                return $this->errorResponse(
                    'Data tidak ditemukan',
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal dibuat', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            SpasialKabupaten::findOrFail($id)->delete;
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

            $olahan = SpasialKabupaten::whereIn('id', $ids)->pluck('id')->toArray();

            $notFoundIds = array_diff($ids, $olahan);

            if (!empty($notFoundIds)) {
                return $this->errorResponse('Data dengan ID berikut tidak ditemukan: ' . implode(', ', $notFoundIds), Response::HTTP_NOT_FOUND);
            }

            SpasialKabupaten::whereIn('id', $olahan)->delete();

            return $this->successResponse('Data spasial berhasil dihapus', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
