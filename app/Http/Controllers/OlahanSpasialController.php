<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Traits\ApiResponse;
use App\Models\{OlahanSpasial, Sektor, SpasialKabupaten, SpasialProvinsi};
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\OlahanSpasialResource;
use Exception;

class OlahanSpasialController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $perPage = $request->per_page ?? 10;
            $olahanQuery = OlahanSpasial::where('opd_provinsi_id', $user->user_opd_provinsi()->first()->opd_provinsi_id)->orderBy('created_at', 'desc');
            if ($search = $request->query('search')) {
                $olahanQuery->where('judul', 'like', '%' . $search . '%');
            }

            if ($opdProvId = $request->query('opd_provinsi_id')) {
                $olahanQuery->where('opd_provinsi_id', $opdProvId);
            }

            if ($sektorSpasial = $request->query('sektor_spasial_id')) {
                $olahanQuery->where('sektor_spasial_id', $sektorSpasial);
            }

            $data = $olahanQuery->paginate($perPage);
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    OlahanSpasialResource::collection($data),
                    'Data Olahan Spasial berhasil diambil',
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
            $req = $request->validate([
                'sektor_spasial_id' => 'required|exists:sektor_spasial,id',
                'judul' => 'required|string',
                'konten' => 'required|string',
                'status' => 'required|in:public,private',
            ]);

            $req['opd_provinsi_id'] = $request->user()->user_opd_provinsi()->first()->opd_provinsi_id;

            OlahanSpasial::create($req);

            return $this->successResponse('Data Olahan Spasial berhasil dibuat', Response::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(string $id)
    {
        try {
            // Mencari data sektor dengan id
            $data = OlahanSpasial::find($id);

            // Mengecek apabila terdapat data dengan id yang sama
            if ($data) {
                return $this->successResponseWithData(
                    OlahanSpasialResource::make($data),
                    'Data Olahan Spasial berhasil diambil',
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

    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();
            $req = $request->validate([
                'sektor_spasial_id' => 'required|exists:sektor_spasial,id',
                'judul' => 'required|string',
                'konten' => 'required|string',
                'status' => 'required|in:public,private',
            ]);

            $req['opd_provinsi_id'] = $user->user_opd_provinsi()->first()->opd_provinsi_id;

            $data = OlahanSpasial::find($id);

            if ($data) {
                $data->update($req);

                return $this->successResponse('Data berhasil diubah', Response::HTTP_CREATED);
            } else {
                return $this->errorResponse(
                    'Data tidak ditemukan',
                    Response::HTTP_NOT_FOUND
                );
            }
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal dibuat', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            OlahanSpasial::findOrFail($id)->delete;
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

            $olahan = OlahanSpasial::whereIn('id', $ids)->pluck('id')->toArray();

            $notFoundIds = array_diff($ids, $olahan);

            if (!empty($notFoundIds)) {
                return $this->errorResponse('Data dengan ID berikut tidak ditemukan: ' . implode(', ', $notFoundIds), Response::HTTP_NOT_FOUND);
            }

            OlahanSpasial::whereIn('id', $olahan)->delete();

            return $this->successResponse('Data olahan berhasil dihapus', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
