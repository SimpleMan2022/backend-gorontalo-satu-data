<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{HasilData, Variabel, RekapHasil, Periode, Enumerator, OpdProvinsi, OpdKabupaten};
use App\Http\Resources\RekapHasilResource;
use App\Http\Traits\ApiResponse;
use Exception;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Storage;
use Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class RekapHasilController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 10;

            $hasilQuery = RekapHasil::orderBy('created_at', 'desc');

            if ($enumeratorId = $request->query('enumerator_id')) {
                $hasilQuery->where('enumerator_id', $enumeratorId);
            }

            if ($periodeId = $request->query('periode_id')) {
                $hasilQuery->where('periode_id', $periodeId);
            }
            if ($indikatorId = $request->query('indikator_id')) {
                $hasilQuery->join('hasil_data', 'hasil_data.rekap_hasil_id', '=', 'rekap_hasil.id')
                ->join('variabel','variabel.id', '=', 'hasil_data.variabel_id')
                ->select('rekap_hasil.*', DB::raw('MAX(variabel.indikator_id) as indikator_id'))
                ->where('indikator_id', $indikatorId)
                ->groupBy('rekap_hasil.id');
            }

            $data = $hasilQuery->paginate($perPage);
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    RekapHasilResource::collection($data),
                    'Data Hasil berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse('Data masih kosong', Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(string $id)
    {
        try {
            $data = RekapHasil::find($id);

            // Mengecek apabila terdapat data dengan id yang sama
            if ($data) {
                return $this->successResponseWithData(
                    RekapHasilResource::make($data),
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
                'status' => 'required|in:v2,v3,tolak,terima,revisi',
            ]);

            $rekapHasil = RekapHasil::findOrFail($id);
            $rekapHasil->update($validated);

            return $this->successResponse('Data berhasil diubah', Response::HTTP_CREATED);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal diperbarui', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function multiUpdate(Request $request) {
        try {
            $validated = $request->validate([
                '*.id' => 'required|integer|exists:rekap_hasil,id',
                '*.status' => 'required|in:v2,v3,tolak,terima,revisi',
            ]);

            $ids = collect($validated)->pluck('id');
            $rekapHasil = RekapHasil::whereIn('id', $ids)->get();

            foreach ($rekapHasil as $item) {
                $item->update([
                    'status' => collect($validated)->where('id', $item->id)->first()['status']
                ]);
            }

            return $this->successResponse('Data berhasil diubah', Response::HTTP_CREATED);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal diperbarui', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            // Menghapus data sektor sesuai request $sektor
            $rekapHasil = RekapHasil::findOrFail($id);
            $rekapHasil->delete();
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

            $rekapHasil = RekapHasil::whereIn('id', $ids)->pluck('id')->toArray();

            $notFoundIds = array_diff($ids, $rekapHasil);

            if (!empty($notFoundIds)) {
                return $this->errorResponse('Data dengan ID berikut tidak ditemukan: ' . implode(', ', $notFoundIds), Response::HTTP_NOT_FOUND);
            }

            RekapHasil::whereIn('id', $rekapHasil)->delete();

            return $this->successResponse('Data rekapHasil berhasil dihapus', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
