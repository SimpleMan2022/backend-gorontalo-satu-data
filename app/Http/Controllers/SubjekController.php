<?php

namespace App\Http\Controllers;

use App\Http\Resources\SubjekResource;
use Illuminate\Http\Request;
use App\Models\{Subjek, PivotIndikatorSubjek};
use App\Http\Traits\ApiResponse;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;

class SubjekController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $perPage = $request->per_page ?? 10;
            $data = Subjek::orderBy('created_at', 'desc');

            if ($search = $request->query('search')) {
                $data->where('nama', 'like', '%' . $search . '%');
            }
            if ($user->hasRole('opd_provinsi')) {
                $opdProvinsiId = $user->user_opd_provinsi()->first()->opd_provinsi_id;
                $data->where('opd_provinsi_id', $opdProvinsiId);
                if ($indikatorId = $request->query('indikator_id')) {
                    $data->whereHas('indikators', function ($query) use ($indikatorId) {
                        $query->where('indikator.id', $indikatorId);
                    });
                }
            }
            $data = $data->paginate($perPage);
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    SubjekResource::collection($data),
                    'Data subjek berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse('Data masih kosong', Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function multiStore(Request $request)
    {
        try {
            $data = $request->json()->all();
            $validator = Validator::make($data, [
            '*.nama' => 'required|string|max:255',  // Validasi setiap 'nama' di dalam array sebagai string
            '*.indikator_id' => 'required|string',  // Validasi setiap 'indikator_id' di dalam array sebagai string
            ]);

            if ($validator->fails()) {
            return $this->errorResponseWithData($validator->errors(), 'Data gagal dibuat', Response::HTTP_BAD_REQUEST);
            }

            $req = $validator->validated();
            // Validasi data
            if (empty($data)) {
            return $this->errorResponse('Data tidak boleh kosong', Response::HTTP_BAD_REQUEST);
            }

            foreach ($req as $item) {
            // Cek apakah subjek sudah ada berdasarkan nama
            $subjek = Subjek::whereRaw('LOWER(nama) = ?', [strtolower($request->nama)])->first();

            // Jika subjek belum ada, maka buat baru
            if (!$subjek) {
                $subjek = Subjek::create([
                'nama' => $item['nama'],
                ]);
            }

            // Pisahkan indikator_id berdasarkan koma dan hilangkan spasi
            $indikatorIds = array_filter(array_map('trim', explode(',', $item['indikator_id'])), function($value) {
                return $value !== '';
            });
            foreach ($indikatorIds as $indikatorId) {
                if (!empty($indikatorId)) {
                // Buat data pada pivot jika belum ada
                PivotIndikatorSubjek::firstOrCreate([
                    'indikator_id' => $indikatorId,
                    'subjek_id' => $subjek->id,
                ]);
                }
            }
            }

            return $this->successResponse(
            'Data subjek berhasil dibuat',
            Response::HTTP_CREATED
            );
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal dibuat', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            $req = $request->validate([
                'nama' => 'required|string|max:255',  // Validasi setiap 'nama' di dalam array sebagai string
                // 'opd_provinsi_id' => 'required',  // Validasi setiap 'indikator_id' di dalam array sebagai string
            ]);

            $req['opd_provinsi_id'] = $request->user()->user_opd_provinsi()->first()->opd_provinsi_id;

            // Cek apakah subjek sudah ada berdasarkan nama (case insensitive)
            $subjek = Subjek::create($req);

            return $this->successResponse(
                'Data subjek berhasil dibuat',
                Response::HTTP_CREATED
            );
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal dibuat', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function storePivot(Request $request)
    {
        try {
            $req = $request->validate([
                'subjek_id' => 'required|integer',  // Validasi setiap 'nama' di dalam array sebagai string
                'indikator_id' => 'required|integer',  // Validasi setiap 'indikator_id' di dalam array sebagai string                
            ]);

            PivotIndikatorSubjek::create($req);

            return $this->successResponse(
            'Data pivot berhasil dibuat',
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
            $data = Subjek::find($id);

            // Mengecek apabila terdapat data dengan id yang sama
            if ($data) {
                return $this->successResponseWithData(
                    $data,
                    'Data subjek berhasil diambil',
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
            // Membuat Validasi
            $req = $request->validate([
                'nama' => 'required|string|max:255',  // Validasi setiap 'nama' di dalam array sebagai string
            ]);

            $subjek = Subjek::find($id);

            if ($subjek) {
                $subjek->update($req);
                return $this->successResponse(
                    'Data subjek berhasil diubah',
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
            $subjek = Subjek::findOrFail($id);
            $subjek->delete();
            return $this->successResponse('Data berhasil dihapus', Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroyPivot($id)
    {
        try {
            // Menghapus data sektor sesuai request $sektor
            $pivot = PivotIndikatorSubjek::findOrFail($id);
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

            $subjek = Subjek::whereIn('id', $ids)->pluck('id')->toArray();

            $notFoundIds = array_diff($ids, $subjek);

            if (!empty($notFoundIds)) {
                return $this->errorResponse('Data dengan ID berikut tidak ditemukan: ' . implode(', ', $notFoundIds), Response::HTTP_NOT_FOUND);
            }

            Subjek::whereIn('id', $subjek)->delete();

            return $this->successResponse('Data subjek berhasil dihapus', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
