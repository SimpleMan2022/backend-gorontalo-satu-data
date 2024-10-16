<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sektor;
use App\Models\Subjek;
use App\Http\Traits\ApiResponse;
use App\Models\AntarWaktu;
use App\Models\Indikator;
use App\Models\User;
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

            // return response()->json([
            //     'message' => 'Total data berhasil diambil',
            //     'total_sektor' => $total_sektor,
            //     'total_subjek' => $total_subjek,
            //     'total_user' => $total_user,
            //     'total_antar_waktu' => $total_antar_waktu
            // ], Response::HTTP_OK);

        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function ChartDonut(Request $request)
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


    public function ChartBar(Request $request)
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
}
