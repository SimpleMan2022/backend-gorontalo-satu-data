<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sektor;
use App\Models\Subjek;
use App\Http\Traits\ApiResponse;
use App\Models\AntarWaktu;
use App\Models\EnumeratorKabupaten;
use App\Models\EnumeratorProvinsi;
use App\Models\Indikator;
use App\Models\OpdKabupaten;
use App\Models\PivotIndikatorSubjek;
use App\Models\RekapHasil;
use App\Models\User;
use App\Models\Variabel;
use Exception;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends Controller
{
    // Memanggil Traits Api Response
    use ApiResponse;

    public function AdminView(Request $request)
    {
        try {
            $total_sektor = Sektor::count();
            $total_subjek = Subjek::count(); 
            $total_user = User::count();
            $total_antar_waktu = AntarWaktu::count();

            $data = [
                'total_sektor' => $total_sektor,
                'total_subjek' => $total_subjek,
                'total_user' => $total_user,
                'total_antar_waktu' => $total_antar_waktu
            ];

            return $this->successResponseWithData(
                $data,
                'success', 
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function ChartDonutAdmin(Request $request)
    {
        try {
            // Count the total number of indikator
            $total_indikator = Indikator::count();

            // Count the total number of each status in the Indikator model
            $pending_count = Indikator::where('status', 'pending')->count();
            $public_count = Indikator::where('status', 'public')->count();
            $private_count = Indikator::where('status', 'private')->count();

            // Calculate percentages with two decimal points
            $pending_percentage = $total_indikator > 0 ? ($pending_count / $total_indikator) * 100 : 0;
            $public_percentage = $total_indikator > 0 ? ($public_count / $total_indikator) * 100 : 0;
            $private_percentage = $total_indikator > 0 ? ($private_count / $total_indikator) * 100 : 0;

            // Create the response structure with percentages
            $status_counts = [
                [
                    'status_indikator' => 'pending',
                    'jumlah' => $pending_count,
                    'persentase' => number_format($pending_percentage, 2)
                ],
                [
                    'status_indikator' => 'public',
                    'jumlah' => $public_count,
                    'persentase' => number_format($public_percentage, 2)
                ],
                [
                    'status_indikator' => 'private',
                    'jumlah' => $private_count,
                    'persentase' => number_format($private_percentage, 2)
                ]
            ];

            return $this->successResponseWithData(
                $status_counts,
                'success',
                Response::HTTP_OK
            );

        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }


    public function ChartBarAdmin(Request $request)
    {
        try {
            // Retrieve data grouped by 'antar_waktu' and 'sektor' with counts
            $data = AntarWaktu::with(['indikator.sektor'])
                ->get()
                ->map(function($antarWaktu) {
                    // Group indikator by sektor and count them
                    $sektorCounts = $antarWaktu->indikator
                        ->groupBy('sektor.nama')
                        ->map(function($indikators) {
                            return $indikators->count();
                        });

                    return [
                        'tahun' => $antarWaktu->tahun,
                        'nama' => $antarWaktu->nama,
                        'sektor' => $sektorCounts
                    ];
                });

            // Use the successResponseWithData method from ApiResponse trait
            return $this->successResponseWithData(
                $data,
                'success',
                Response::HTTP_OK
            );

        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function OpdProvinsiView(Request $request)
    {
        try {
            // Check if opd_provinsi_id is provided
            $opd_provinsi_id = $request->input('opd_provinsi_id');
            if (!$opd_provinsi_id) {
                return response()->json([
                    'error' => 'opd_provinsi_id is required'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Count all entries
            $total_variabel = Variabel::whereHas('indikator', function($query) use ($opd_provinsi_id) {
                $query->where('opd_provinsi_id', $opd_provinsi_id);
            })->count();

            $total_indikator = Indikator::where('opd_provinsi_id', $opd_provinsi_id)->count();

            // Count the id from pivot_indikator_subjek where indikator_id is related to the opd_provinsi_id
            $total_subjek = PivotIndikatorSubjek::whereHas('indikator', function($query) use ($opd_provinsi_id) {
                $query->where('opd_provinsi_id', $opd_provinsi_id);
            })->count('id');

            $total_enumerator_provinsi = EnumeratorProvinsi::where('opd_provinsi_id', $opd_provinsi_id)->count(); // Assuming User does not have opd_provinsi_id

            // Prepare data response
            $data = [
                'total_variabel' => $total_variabel,
                'total_indikator' => $total_indikator,
                'total_subjek' => $total_subjek, // Count of pivot table records
                'total_enumerator_provinsi' => $total_enumerator_provinsi,
            ];

            return $this->successResponseWithData(
                $data,
                'success', 
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function ChartDonutOpdProvinsi(Request $request)
    {
        try {
            // Validate that opd_provinsi_id is provided
            $opd_provinsi_id = $request->input('opd_provinsi_id');
            if (!$opd_provinsi_id) {
                return response()->json([
                    'error' => 'opd_provinsi_id is required'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Count the total number of indikator filtered by opd_provinsi_id
            $total_rekap_hasil = RekapHasil::whereHas('periode', function ($query) use ($opd_provinsi_id) {
                $query->where('opd_provinsi_id', $opd_provinsi_id);
            })->count();

            // Count the total number of each status in the RekapHasil model, filtered by opd_provinsi_id
            $terima_count = RekapHasil::whereHas('periode', function ($query) use ($opd_provinsi_id) {
                $query->where('opd_provinsi_id', $opd_provinsi_id);
            })->where('status', 'terima')->count();

            $revisi_count = RekapHasil::whereHas('periode', function ($query) use ($opd_provinsi_id) {
                $query->where('opd_provinsi_id', $opd_provinsi_id);
            })->where('status', 'revisi')->count();

            $tolak_count = RekapHasil::whereHas('periode', function ($query) use ($opd_provinsi_id) {
                $query->where('opd_provinsi_id', $opd_provinsi_id);
            })->where('status', 'tolak')->count();

            // Calculate percentages with two decimal points
            $terima_percentage = $total_rekap_hasil > 0 ? ($terima_count / $total_rekap_hasil) * 100 : 0;
            $revisi_percentage = $total_rekap_hasil > 0 ? ($revisi_count / $total_rekap_hasil) * 100 : 0;
            $tolak_percentage = $total_rekap_hasil > 0 ? ($tolak_count / $total_rekap_hasil) * 100 : 0;

            // Create the response structure with percentages
            $status_counts = [
                [
                    'status_rekap_hasil' => 'terima',
                    'jumlah' => $terima_count,
                    'persentase' => number_format($terima_percentage, 2)
                ],
                [
                    'status_rekap_hasil' => 'revisi',
                    'jumlah' => $revisi_count,
                    'persentase' => number_format($revisi_percentage, 2)
                ],
                [
                    'status_rekap_hasil' => 'tolak',
                    'jumlah' => $tolak_count,
                    'persentase' => number_format($tolak_percentage, 2)
                ]
            ];

            return $this->successResponseWithData(
                $status_counts,
                'success',
                Response::HTTP_OK
            );

        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function ChartBarOpdProvinsi(Request $request)
    {
        try {
            // Validate that opd_provinsi_id is provided
            $opd_provinsi_id = $request->input('opd_provinsi_id');
            if (!$opd_provinsi_id) {
                return response()->json([
                    'error' => 'opd_provinsi_id is required'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Retrieve data grouped by 'antar_waktu' and 'sektor' with counts, filtered by opd_provinsi_id
            $data = AntarWaktu::with(['indikator' => function ($query) use ($opd_provinsi_id) {
                    // Filter the indikator by the opd_provinsi_id
                    $query->where('opd_provinsi_id', $opd_provinsi_id);
                }, 'indikator.sektor'])
                ->get()
                ->map(function($antarWaktu) {
                    // Group indikator by sektor and count them
                    $sektorCounts = $antarWaktu->indikator
                        ->groupBy('sektor.nama')
                        ->map(function($indikators) {
                            return $indikators->count();
                        });

                    return [
                        'tahun' => $antarWaktu->tahun,
                        'nama' => $antarWaktu->nama,
                        'sektor' => $sektorCounts
                    ];
                });

            // Use the successResponseWithData method from ApiResponse trait
            return $this->successResponseWithData(
                $data,
                'success',
                Response::HTTP_OK
            );

        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function OpdKabupatenView(Request $request)
    {
        try {
            // Check if opd_kabupaten_id is provided
            $opd_kabupaten_id = $request->input('opd_kabupaten_id');
            if (!$opd_kabupaten_id) {
                return response()->json([
                    'error' => 'opd_kabupaten_id is required'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Retrieve the opd_provinsi_id from the OpdKabupaten model
            $opdKabupaten = OpdKabupaten::find($opd_kabupaten_id);
            if (!$opdKabupaten) {
                return response()->json([
                    'error' => 'OpdKabupaten not found'
                ], Response::HTTP_NOT_FOUND);
            }

            // Extract the opd_provinsi_id
            $opd_provinsi_id = $opdKabupaten->opd_provinsi_id;

            // Now proceed with the same logic using the retrieved opd_provinsi_id
            $total_variabel = Variabel::whereHas('indikator', function($query) use ($opd_provinsi_id) {
                $query->where('opd_provinsi_id', $opd_provinsi_id);
            })->count();

            $total_indikator = Indikator::where('opd_provinsi_id', $opd_provinsi_id)->count();

            // Count the id from pivot_indikator_subjek where indikator_id is related to the opd_provinsi_id
            $total_subjek = PivotIndikatorSubjek::whereHas('indikator', function($query) use ($opd_provinsi_id) {
                $query->where('opd_provinsi_id', $opd_provinsi_id);
            })->count('id');

            $total_enumerator_kabupaten = EnumeratorKabupaten::where('opd_kabupaten_id', $opd_kabupaten_id)->count(); // Assuming User does not have opd_provinsi_id

            // Prepare data response
            $data = [
                'total_variabel' => $total_variabel,
                'total_indikator' => $total_indikator,
                'total_subjek' => $total_subjek, // Count of pivot table records
                'total_enumerator_kabupaten' => $total_enumerator_kabupaten,
            ];

            return $this->successResponseWithData(
                $data,
                'success', 
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


}
