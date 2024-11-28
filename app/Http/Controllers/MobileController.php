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
            $enumerator_id = Enumerator::where('user_id', $request->user()->id)->first()->id;
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


    public function variabelByIndikator(Request $request, $indikator)
    {
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
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function EnumeratorIndikator(Request $request)
    {
        try {
            $enumerator_id = Enumerator::where('user_id', $request->user()->id)->first()->id;
            // Validate that enumerator_id is provided
            if (!$enumerator_id) {
                return $this->errorResponse(
                    'enumerator_id is required',
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Get the search query parameter
            $search = $request->query('search');

            // Retrieve data from Enumerator with Indikator details through the pivot table
            $data = Enumerator::with(['enumerator_indikator_status_pending.indikator.variabel' => function ($query) use ($search) {
                $query->select(['id', 'indikator_id', 'nama', 'order', 'tipe']);
            }])->with(['enumerator_indikator_status_pending.indikator' => function ($query) use ($search) {
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
                                'variabel_id' => $pivot->indikator->variabel,
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

    public function RiwayatEnumerator(Request $request)
    {
        try {
            // Validate that enumerator_id is provided
            $enumerator_id = Enumerator::where('user_id', $request->user()->id)->first();
            // Validate that enumerator_id is provided
            if (!$enumerator_id) {
                return $this->errorResponse(
                    'Silahkan login menggunakan enumerator terlebih dahulu',
                    Response::HTTP_BAD_REQUEST
                );
            }

            $query = RekapHasil::with(['indikator.opd_provinsi' => function ($query) {
                $query->select(['id', 'nama', 'logo']);
            }])->with(['indikator.sektor' => function ($query) {
                $query->select(['id', 'nama']);
            }])->where('enumerator_id', $enumerator_id->id);

            if ($status = $request->query('status')) {
                $query->where('status', $status);
            }

            // dd($enumerator_id->enumerator_level);

            switch ($enumerator_id->enumerator_level) {
                case 'provinsi':
                    $data = $query->get()->map(function ($rekap) {
                        return [
                            'id' => $rekap->id,
                            'nama_indikator' => $rekap->indikator->nama,
                            'nama_sektor' => $rekap->indikator->sektor->nama,
                            'status' => $rekap->status === 'v3' ? 'menunggu' : $rekap->status,
                            'nama_opd' => $rekap->indikator->opd_provinsi->nama,
                            'logo_opd' => $rekap->indikator->opd_provinsi->logo,
                            'created_at' => $rekap->created_at,
                        ];
                    });
                    break;
                case 'kabupaten':
                    $data = $query->get()->map(function ($rekap) {
                        return [
                            'id' => $rekap->id,
                            'nama_indikator' => $rekap->indikator->nama,
                            'nama_sektor' => $rekap->indikator->sektor->nama,
                            'status' => $rekap->status === 'v2' ? 'menunggu' : ($rekap->status === 'v3' ? 'diterima' : $rekap->status),
                            'nama_opd' => $rekap->indikator->opd_provinsi->nama,
                            'logo_opd' => $rekap->indikator->opd_provinsi->logo,
                            'created_at' => $rekap->created_at,
                        ];
                    });
                    break;
                    // Add more cases if needed
            }

            // $data = $query->get()->map(function ($rekap) {
            //     return [
            //         'id' => $rekap->id,
            //         'nama_indikator' => $rekap->indikator->nama,
            //         'nama_sektor' => $rekap->indikator->sektor->nama,
            //         'status' => $rekap->status,
            //         'nama_opd' => $rekap->indikator->opd_provinsi->nama,
            //         'logo_opd' => $rekap->indikator->opd_provinsi->logo,
            //         'created_at' => $rekap->created_at,
            //         'updated_at' => $rekap->updated_at,
            //     ];
            // });


            // Get the search query parameter
            // $search = $request->query('search');

            // // Retrieve data from Enumerator with Indikator details through the pivot table
            // $data = Enumerator::with(['rekapHasil' => function ($query) use ($search) {
            //     $query->select(['id', 'nama', 'status']);
            // }])->with(['pivot_enumerator_indikator.indikator' => function ($query) use ($search) {
            //     $query->select(['id', 'nama', 'sektor_id', 'opd_provinsi_id']);
            //     if ($search) {
            //         $query->where('nama', 'like', '%' . $search . '%');
            //     }
            // }])
            //     ->where('id', $enumerator_id)
            //     ->get()
            //     ->flatMap(function ($enumerator) {
            //         return $enumerator->pivot_enumerator_indikator
            //             ->filter(function ($pivot) {
            //                 return $pivot->indikator !== null;
            //             })
            //             ->map(function ($pivot) {
            //                 return [
            //                     'nama_sektor' => $pivot->indikator->sektor->nama,
            //                     'indikator_id' => $pivot->indikator->id,
            //                     'status' => $pivot->status,
            //                     'nama_indikator' => $pivot->indikator->nama,    
            //                     'status_rekap' => $pivot->indikator->rekap_hasil->last(),
            //                     'nama_opd' => $pivot->indikator->opd_provinsi->nama,
            //                     'logo_opd' => $pivot->indikator->opd_provinsi->logo,
            //                     'created_at' => $pivot->created_at,
            //                     'updated_at' => $pivot->updated_at,
            //                 ];
            //             });
            //     });


            return $this->successResponseWithData(
                $data,
                'Data Riwayat Enumerator berhasil diambil',
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
