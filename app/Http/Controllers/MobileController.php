<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Traits\ApiResponse;
use App\Models\Enumerator;
use App\Models\EnumeratorKabupaten;
use App\Models\EnumeratorProvinsi;
use App\Models\RekapHasil;
use App\Models\Variabel;
use App\Models\Indikator;
use Exception;
use Illuminate\Http\Response;

class MobileController extends Controller
{
    use ApiResponse;
    public function EnumeratorView(Request $request)
    {
        try {
            // Check if enumerator_id is provided
            $enumerator_id = $request->query('enumerator_id');
            if (!$enumerator_id) {
                return $this->errorResponse(
                    'enumerator_id is required',
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Check if the enumerator exists
            $enumerator = Enumerator::find($enumerator_id);
            if (!$enumerator) {
                return $this->errorResponse(
                    'Enumerator not found',
                    Response::HTTP_NOT_FOUND
                );
            }

            // Check if enumerator_id exists in EnumeratorKabupaten or EnumeratorProvinsi
            $enumerator_kabupaten = EnumeratorKabupaten::where('enumerator_id', $enumerator_id)->first();
            $enumerator_provinsi = EnumeratorProvinsi::where('enumerator_id', $enumerator_id)->first();

            // If enumerator is not associated with either Kabupaten or Provinsi, return "no data"
            if (!$enumerator_kabupaten && !$enumerator_provinsi) {
                return $this->successResponseWithData(
                    [],
                    'Data Enumerator tidak ditemukan',
                    Response::HTTP_OK
                );
            }

            // Initialize total_indikator count
            $total_indikator = RekapHasil::where('enumerator_id', $enumerator_id)->count();

            // Set totals and opd based on where the enumerator is located
            if ($enumerator_kabupaten) {
                $opd = $enumerator_kabupaten->opd_kabupaten->nama;
                $total_indikator_diverifikasi = RekapHasil::where('enumerator_id', $enumerator_id)
                    ->where('status', 'v3')
                    ->count();
                $total_indikator_ditolak = RekapHasil::where('enumerator_id', $enumerator_id)
                    ->where('status', 'tolak')
                    ->count();
                $total_indikator_diproses = RekapHasil::where('enumerator_id', $enumerator_id)
                    ->where('status', 'v2')
                    ->count();
            } elseif ($enumerator_provinsi) { // If in EnumeratorProvinsi
                $opd = $enumerator_provinsi->opd_provinsi->nama;
                $total_indikator_diverifikasi = RekapHasil::where('enumerator_id', $enumerator_id)
                    ->where('status', 'terima')
                    ->count();
                $total_indikator_ditolak = RekapHasil::where('enumerator_id', $enumerator_id)
                    ->where('status', 'tolak')
                    ->count();
                $total_indikator_diproses = RekapHasil::where('enumerator_id', $enumerator_id)
                    ->where('status', 'v3')
                    ->count();
            }

            // Prepare data response
            $data = [
                'opd' => $opd,
                'terinput' => $total_indikator,
                'diverifikasi' => $total_indikator_diverifikasi,
                'ditolak' => $total_indikator_ditolak,
                'menunggu' => $total_indikator_diproses,
            ];

            return $this->successResponseWithData(
                $data,
                'Data Enumerator berhasil diambil',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }


    public function variabelByIndikator(Request $request, $indikator) {
        try {
            $data = Variabel::where('indikator_id', $indikator)
                ->select('id', 'indikator_id', 'nama', 'order', 'tipe')
                ->orderBy('order', 'asc')
                ->get();

            return $this->successResponseWithData(
                $data,
                'Data Variabel berhasil diambil',
                Response::HTTP_OK
            );
        }catch(Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function EnumeratorIndikator(Request $request)
    {
        try {
            // Validate that enumerator_id is provided
            $enumerator_id = $request->query('enumerator_id');
            if (!$enumerator_id) {
                return $this->errorResponse(
                    'enumerator_id is required',
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Get the search query parameter
            $search = $request->query('search');

            // Retrieve data from Enumerator with Indikator details through the pivot table
            $data = Enumerator::with(['enumerator_indikator_status_pending.indikator' => function ($query) use ($search) {
                $query->select(['id', 'nama', 'sektor_id', 'opd_provinsi_id']);
                if ($search) {
                    $query->where('nama', 'like', '%' . $search . '%');
                }
            }])
                ->where('id', $enumerator_id)
                ->get()
                ->flatMap(function ($enumerator) {
                    return $enumerator->enumerator_indikator_status_pending
                        ->filter(function ($pivot) {
                            return $pivot->indikator !== null;
                        })
                        ->map(function ($pivot) {
                            return [
                                'nama_sektor' => $pivot->indikator->sektor->nama,
                                'indikator_id' => $pivot->indikator->id,
                                'nama_indikator' => $pivot->indikator->nama,
                                'nama_opd' => $pivot->indikator->opd_provinsi->nama,
                                'logo_opd' => $pivot->indikator->opd_provinsi->logo,
                                'created_at' => $pivot->created_at,
                                'updated_at' => $pivot->updated_at,
                            ];
                        });
                });


            return $this->successResponseWithData(
                $data,
                'Data Enumerator Indikator berhasil diambil',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
