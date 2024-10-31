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
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PivotEnumeratorIndikatorController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 10;

            $pivotQuery = PivotEnumeratorIndikator::orderBy('created_at', 'desc');

            if ($enumeratorId = $request->query('enumerator_id')) {
                $pivotQuery->where('enumerator_id', $enumeratorId);
            }

            if ($indikatorId = $request->query('indikatorId')) {
                $pivotQuery->where('indikatorId', $indikatorId);
            }


            $data = $pivotQuery->paginate($perPage);
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    PivotEnumeratorIndikatorResource::collection($data),
                    'Data indikator berhasil diambil',
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
                'enumerator_id' => 'required|exists:enumerator,id',
                'indikator_id' => 'required|exists:indikator,id'
            ]);
            PivotEnumeratorIndikator::create($validated);
            return $this->successResponse('Data berhasil dibuat', Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal dibuat', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function multiStore(Request $request)
    {
        try {
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
                'Data berhasil dibuat',
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
                    'Data Enumerator Indikator berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'enumerator_id' => 'required|exists:enumerator,id',
                'indikator_id' => 'required|exists:indikator,id'
            ]);

            $pivot = PivotEnumeratorIndikator::findOrFail($id);
            $pivot->update($validated);
            return $this->successResponse('Data berhasil diperbarui', Response::HTTP_CREATED);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
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
            $pivot = PivotEnumeratorIndikator::findOrFail($id);
            $pivot->delete();
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
