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
    
    public function index(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 10;
            $periodeQuery = Periode::orderBy('created_at', 'desc');

            if ($opdProvinsiId = $request->query('opd_provinsi_id')) {
                $periodeQuery->where('opd_provinsi_id', $opdProvinsiId);
            }
            $data = $periodeQuery->paginate($perPage);

            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    PeriodeResource::collection($data),
                    'Data periode berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse('Data masih kosong', Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function aktif()
    {
        try {
            $now = date('Y-m-d');
            $periode = Periode::where('tanggal_mulai', '<=', $now)
                ->where('tanggal_selesai', '>=', $now)
                ->first();

            if (Periode::where('tanggal_mulai', '=', $now)
                ->where('tanggal_selesai', '=', $now)
                ->exists()) {
                return $this->errorResponse('Terdapat periode yang memiliki tanggal mulai dan tanggal selesai yang sama', Response::HTTP_CONFLICT);
            }
            

            if ($periode) {
                return $this->successResponseWithData(PeriodeResource::make($periode), 'Data periode yang sedang aktif', Response::HTTP_OK);
            } else {
                return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
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
                'tanggal_mulai' => 'required|date',
                'tanggal_selesai' => 'required|date',
                'opd_provinsi_id' => 'required|numeric'
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

    public function multiDestroy()
    {
        try {
            $id = request('id');
            $ids = explode(",", $id);

            $periode = Periode::whereIn('id', $ids)->pluck('id')->toArray();

            $notFoundIds = array_diff($ids, $periode);

            if (!empty($notFoundIds)) {
                return $this->errorResponse('Data dengan ID berikut tidak ditemukan: ' . implode(', ', $notFoundIds), Response::HTTP_NOT_FOUND);
            }

            Periode::whereIn('id', $periode)->delete();

            return $this->successResponse('Data periode berhasil dihapus', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
