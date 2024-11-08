<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{HasilData, Variabel, RekapHasil, Periode, Enumerator, Indikator, OpdProvinsi, OpdKabupaten, PivotEnumeratorIndikator};
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
            $hasilQuery = RekapHasil::with(['hasil_data.variabel.indikator', 'enumerator', 'periode.opd_provinsi'])
                ->orderBy('created_at', 'desc');

            if ($enumeratorId = $request->query('enumerator_id')) {
                $hasilQuery->where('enumerator_id', $enumeratorId);
            }

            if ($periodeId = $request->query('periode_id')) {
                $hasilQuery->where('periode_id', $periodeId);
            }

            if ($indikatorId = $request->query('indikator_id')) {
                $hasilQuery->whereHas('hasil_data.variabel.indikator', function ($query) use ($indikatorId) {
                    $query->where('id', $indikatorId);
                });
            }

            $data = $hasilQuery->first();

            if ($data) {
                // Jika data ditemukan, format hasil seperti biasa
                $allHasilData = HasilData::with('variabel')
                    ->whereHas('rekap_hasil', function ($query) use ($request) {
                        if ($enumeratorId = $request->query('enumerator_id')) {
                            $query->where('enumerator_id', $enumeratorId);
                        }
                        if ($periodeId = $request->query('periode_id')) {
                            $query->where('periode_id', $periodeId);
                        }
                    })
                    ->orderBy('created_at', 'desc')
                    ->get();

                // Group hasil_data into pairs
                $groupedHasilData = collect();
                foreach ($allHasilData->chunk(2) as $chunk) {
                    $groupedHasilData->push(
                        $chunk->map(function ($hasil) {
                            return [
                                'hasil_data_id' => $hasil->id,
                                'nama' => $hasil->variabel->nama,
                                'tipe' => $hasil->variabel->tipe,
                                'konten' => $hasil->konten,
                            ];
                        })->values()
                    );
                }

                $formattedData = [
                    'id' => (int) $indikatorId,
                    'nama' => $data->hasil_data->first()->variabel->indikator->nama,
                    'nama_opd' => $data->periode->opd_provinsi->nama,
                    'enumerator' => [
                        'id' => $data->enumerator->id,
                        'nama' => $data->enumerator->nama,
                        'jabatan' => $data->enumerator->jabatan,
                        'enumerator_level' => $data->enumerator->enumerator_level,
                    ],
                    'periode' => [
                        'id' => $data->periode->id,
                        'nama' => $data->periode->nama,
                        'tanggal_mulai' => $data->periode->tanggal_mulai,
                        'tanggal_selesai' => $data->periode->tanggal_selesai,
                    ],
                    'variabel' => $data->hasil_data->map(function ($hasil) {
                        return [
                            'id' => $hasil->variabel->id,
                            'nama' => $hasil->variabel->nama,
                            'tipe' => $hasil->variabel->tipe,
                        ];
                    }),
                    'hasil_data' => $groupedHasilData,
                    'status' => $data->status,
                    'created_at' => $data->created_at,
                    'updated_at' => $data->updated_at,
                ];

                return $this->successResponseWithData(
                    $formattedData,
                    'Rekap hasil berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                // Jika `indikator_id` ada tapi data tidak ditemukan, lakukan query tambahan untuk mendapatkan informasi indikator dan variabel
                if ($indikatorId) {
                    $indikator = Indikator::with(['variabel', 'enumerators', 'opd_provinsi'])
                        ->where('id', $indikatorId)
                        ->first();

                    if ($indikator) {
                        // Format data tanpa `hasil_data`
                        $formattedData = [
                            'id' => (int) $indikatorId,
                            'nama' => $indikator->nama,
                            'nama_opd' => $indikator->opd_provinsi->nama,
                            'enumerator' => $indikator->enumerators()->first() ? [
                                'id' => $indikator->enumerators()->first()->id,
                                'nama' => $indikator->enumerators()->first()->nama,
                                'jabatan' => $indikator->enumerators()->first()->jabatan,
                                'enumerator_level' => $indikator->enumerators()->first()->enumerator_level,
                            ] : null,
                            'periode' => $indikator->opd_provinsi()->first()->periode()->first()->nama ?? null,
                            'variabel' => $indikator->variabel->map(function ($variabel) {
                                return [
                                    'id' => $variabel->id,
                                    'nama' => $variabel->nama,
                                    'tipe' => $variabel->tipe,
                                ];
                            }),
                            'hasil_data' => [],
                            'status' => 'pending',
                            'created_at' => null,
                            'updated_at' => null,
                        ];

                        return $this->successResponseWithData(
                            $formattedData,
                            'Indikator ditemukan tanpa hasil data',
                            Response::HTTP_OK
                        );
                    }
                }

                // Jika data masih kosong, kembalikan pesan 404
                return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
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

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:v2,v3,tolak,terima,revisi',
            ]);

            $indikator = RekapHasil::join('hasil_data', 'rekap_hasil.id', '=', 'hasil_data.rekap_hasil_id')
                ->join('variabel', 'variabel.id', '=', 'hasil_data.variabel_id')
                ->select('rekap_hasil.*', DB::raw('MAX(variabel.indikator_id) as indikator_id'))
                ->groupBy('rekap_hasil.id')
                ->where('rekap_hasil.id', $id)
                ->first();

            $rekapHasil = RekapHasil::findOrFail($id);
            if ($request->status == 'revisi' || $request->status == 'tolak') {
                $pivot = PivotEnumeratorIndikator::where('enumerator_id', $rekapHasil->enumerator_id)
                    ->where('indikator_id', $indikator->indikator_id) // Fix incorrect property access
                    ->first();

                if ($pivot) {
                    $pivot->update([
                        'status' => 'pending'
                    ]);
                }
            }

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


    public function multiUpdate(Request $request)
    {
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
