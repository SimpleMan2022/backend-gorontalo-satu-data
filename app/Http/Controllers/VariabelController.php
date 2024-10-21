<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Variabel, Indikator};
use App\Http\Resources\VariabelResource;
use App\Http\Traits\ApiResponse;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;

class VariabelController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 10;
            $variabelQuery = Variabel::orderBy('created_at', 'desc');

            if ($indikatorId = $request->query('indikator_id')) {
                $variabelQuery->where('indikator_id', $indikatorId);
            }

            $data = $variabelQuery->paginate($perPage);

            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    VariabelResource::collection($data),
                    'Data variabel berhasil diambil',
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
                'indikator_id' => 'required|exists:indikator,id',
                'nama' => 'required',
                'tipe' => 'required',
            ]);

            $req['order'] = Variabel::where('indikator_id', $request->indikator_id)->count();
            Variabel::create($req);
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
            $data = Variabel::find($id);

            // Mengecek apabila terdapat data dengan id yang sama
            if ($data) {
                return $this->successResponseWithData(
                    VariabelResource::make($data),
                    'Data variabel berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $req = $request->validate([
                'indikator_id' => 'required|exists:indikator,id',
                'nama' => 'required',
                'tipe' => 'required',
            ]);

            $data = Variabel::find($id);

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
            return $this->errorResponseWithData($e->errors(), 'Data gagal diubah', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function tukarPosisi(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'order' => 'required'
            ]);

            $variabel = Variabel::find($id);

            if ($variabel) {
                $variabel_tukar = Variabel::where('order', $validated['order'])
                    ->where('indikator_id', $variabel->indikator_id)
                    ->first();

                if ($variabel_tukar) {
                    $variabel_tukar->update(['order' => $variabel->order]);
                } else {
                    return $this->errorResponse('Data tidak ditemukan', Response::HTTP_INTERNAL_SERVER_ERROR);
                }

                $variabel->update(['order' => $request->order]);

                return $this->successResponse('Data berhasil diubah', Response::HTTP_CREATED);
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

    public function destroy($id)
    {
        try {
            Variabel::findOrFail($id)->delete;
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

            $variabel = Variabel::whereIn('id', $ids)->pluck('id')->toArray();

            $notFoundIds = array_diff($ids, $variabel);

            if (!empty($notFoundIds)) {
                return $this->errorResponse('Data dengan ID berikut tidak ditemukan: ' . implode(', ', $notFoundIds), Response::HTTP_NOT_FOUND);
            }

            Variabel::whereIn('id', $variabel)->delete();

            return $this->successResponse('Data variabel berhasil dihapus', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
