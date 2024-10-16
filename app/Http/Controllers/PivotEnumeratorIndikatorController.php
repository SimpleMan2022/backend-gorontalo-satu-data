<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Enumerator;
use App\Models\Indikator;
use App\Models\PivotEnumeratorIndikator;
use App\Http\Traits\ApiResponse;
use Exception;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Resources\PivotEnumeratorIndikatorResource;

class PivotEnumeratorIndikatorController extends Controller
{
    use ApiResponse;

    public function index(Request $request) {
        try {
            $perPage = $request->per_page ?? 20;
            $data = PivotEnumeratorIndikator::orderBy('created_at', 'desc')->paginate($perPage);
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data, 
                    PivotEnumeratorIndikatorResource::collection($data),
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


    public function byEnumerator(Request $request, $id) {
        try {
            $perPage = $request->per_page ?? 20;
            $data = PivotEnumeratorIndikator::where('enumerator_id', $id)->orderBy('created_at', 'desc')->paginate($perPage);
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data, 
                    PivotEnumeratorIndikatorResource::collection($data),
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

    public function byIndikator(Request $request, $id) {
        try {
            $perPage = $request->per_page ?? 20;
            $data = PivotEnumeratorIndikator::where('indikator_id', $id)->orderBy('created_at', 'desc')->paginate($perPage);
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data, 
                    PivotEnumeratorIndikatorResource::collection($data),
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

    public function store(Request $request) {
        try {
            $validated = $request->validate([
                'enumerator_id' => 'required',
                'indikator_id' => 'required'
            ]);
            // dd($validated);
            PivotEnumeratorIndikator::create($validated);
            return $this->successResponse('Data berhasil dibuat', Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal dibuat', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function multiStore(Request $request) {
        try {
            // $validated = $request->validate([
            //     'opd_provinsi_id' => 'required',
            //     'sektor_id' => 'required'
            // ]);

            $req = $request->validate([
                '*.enumerator_id' => 'required|integer|exists:enumerator,id',  // Validasi setiap 'nama' di dalam array sebagai string
                '*.indikator_id' => 'required|integer|exists:indikator,id',  // Validasi setiap 'indikator_id' di dalam array sebagai integer dan harus ada di tabel 'indikators'
            ]);

            foreach ($req as $item) {
                // Buat data pada pivot jika belum ada
                $pivot = PivotEnumeratorIndikator::create([
                    'enumerator_id' => $item['enumerator_id'],
                    'indikator_id' => $item['indikator_id'],
                ]);
            }

            return $this->successResponse(
                'success',
                Response::HTTP_OK
            );
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
            $data = PivotEnumeratorIndikator::find($id);

            // Mengecek apabila terdapat data dengan id yang sama
            if ($data) {
                return $this->successResponseWithData(
                    PivotEnumeratorIndikatorResource::make($data),
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

    public function update(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'enumerator_id' => 'required',
                'indikator_id' => 'required'
            ]);

            $pivot = PivotEnumeratorIndikator::find($id);
            $pivot->update($validated);
            return $this->successResponse('Data berhasil diperbarui', Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal diperbarui', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(string $id)
    {
        try {

            // Menghapus data sektor sesuai request $sektor
            $pivot = PivotEnumeratorIndikator::find($id);
            $pivot->delete();
            return $this->successResponse('Data berhasil dihapus', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function multiDestroy()
    {
        try {
            $id = request('id');
            $ids = explode(",", $id);

            $pivot = PivotEnumeratorIndikator::whereIn('id', $ids)->pluck('id')->toArray();

            $notFoundIds = array_diff($ids, $pivot);

            if (!empty($notFoundIds)) {
                return $this->errorResponse('Data dengan ID berikut tidak ditemukan: ' . implode(', ', $notFoundIds), Response::HTTP_NOT_FOUND);
            }

            PivotEnumeratorIndikator::whereIn('id', $pivot)->delete();

            return $this->successResponse('Data pivot enumerator indikator berhasil dihapus', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
