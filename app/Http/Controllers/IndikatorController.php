<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Indikator, Sektor, AntarWaktu, OpdProvinsi};
use App\Http\Resources\IndikatorResource;
use App\Http\Traits\ApiResponse;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;

class IndikatorController extends Controller
{
    use ApiResponse;

    public function index(Request $request) {
        try {
            $perpage = $request->per_page ?? 10;
            $data = Indikator::orderBy('created_at', 'desc')->paginate($perpage);
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    IndikatorResource::collection($data), 
                    'success', 
                    Response::HTTP_OK);
            } else {
                return response()->json([
                    'message' => 'Data masih kosong.'
                ]);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function bySektor($sektor) {
        try {
            $data = Indikator::where('sektor_id',$sektor)->orderBy('created_at', 'desc')->get();
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    IndikatorResource::collection($data), 
                    'success', 
                    Response::HTTP_OK);
            } else {
                return response()->json([
                    'message' => 'Data masih kosong.'
                ]);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function byAntarWaktu($antar_waktu) {
        try {
            $data = Indikator::where('antar_waktu_id',$antar_waktu)->orderBy('created_at', 'desc')->get();
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    IndikatorResource::collection($data), 
                    'success', 
                    Response::HTTP_OK);
            } else {
                return response()->json([
                    'message' => 'Data masih kosong.'
                ]);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function byOpdProvinsi($opd_provinsi) {
        try {
            $data = Indikator::where('opd_provinsi_id',$opd_provinsi)->orderBy('created_at', 'desc')->get();
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    IndikatorResource::collection($data), 
                    'success', 
                    Response::HTTP_OK);
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
                'nama' => 'required',
                'sektor_id' => 'required',
                'antar_waktu_id' => 'required',
                'opd_provinsi_id' => 'required',
                'status' => 'required',
            ]);

            $indikator = Indikator::create($validated);
            
            return $this->successResponse('Data berhasil dibuat', Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal dibuat', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id) {
        try {
            // Mencari data sektor dengan id
            $data = Indikator::find($id);

            // Mengecek apabila terdapat data dengan id yang sama
            if ($data) {
                return $this->successResponseWithData(
                    IndikatorResource::make($data),
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

    public function update(Request $request, $id) {
        try {
            $validated = $request->validate([
                'nama' => 'required',
                'sektor_id' => 'required',
                'antar_waktu_id' => 'required',
                'opd_provinsi_id' => 'required',
                'status' => 'required',
            ]);

            $indikator = Indikator::find($id)->update($validated);
            
            return $this->successResponse('Data berhasil diperbarui', Response::HTTP_CREATED);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal diperbarui', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id) {
        try {
            // Menghapus data sektor sesuai request $sektor
            Indikator::find($id)->delete();
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
            $indikator = Indikator::whereIn('id', $ids)->delete();

            return $this->successResponse('Data berhasil dihapus', Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
