<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sektor;
use App\Models\Subjek;
use App\Http\Traits\ApiResponse;
use App\Models\AntarWaktu;
use App\Models\Enumerator;
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
                'Data Admin berhasil diambil',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
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
                'Data Admin berhasil diambil',
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
                ->map(function ($antarWaktu) {
                    // Group indikator by sektor and count them
                    $sektorCounts = $antarWaktu->indikator
                        ->groupBy('sektor.nama')
                        ->map(function ($indikators) {
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
                'Data Admin berhasil diambil',
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
                return $this->errorResponse(
                    'opd_provinsi_id is required',
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Count all entries
            $total_variabel = Variabel::whereHas('indikator', function ($query) use ($opd_provinsi_id) {
                $query->where('opd_provinsi_id', $opd_provinsi_id);
            })->count();

            $total_indikator = Indikator::where('opd_provinsi_id', $opd_provinsi_id)->count();

            // Count the id from pivot_indikator_subjek where indikator_id is related to the opd_provinsi_id
            $total_subjek = PivotIndikatorSubjek::whereHas('indikator', function ($query) use ($opd_provinsi_id) {
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
                'Data Opd Provinsi berhasil diambil',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function ChartDonutOpdProvinsi(Request $request)
    {
        try {
            // Validate that opd_provinsi_id is provided
            $opd_provinsi_id = $request->input('opd_provinsi_id');
            if (!$opd_provinsi_id) {
                return $this->errorResponse(
                    'opd_provinsi_id is required',
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Count the total number of indikator filtered by opd_provinsi_id
            $total_rekap_hasil = RekapHasil::whereHas('periode', function ($query) use ($opd_provinsi_id) {
                $query->where('opd_provinsi_id', $opd_provinsi_id);
            })
                ->whereIn('status', ['terima', 'revisi', 'tolak', 'v3'])
                ->count();

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

            $v3_count = RekapHasil::whereHas('periode', function ($query) use ($opd_provinsi_id) {
                $query->where('opd_provinsi_id', $opd_provinsi_id);
            })->where('status', 'v3')->count();

            // Calculate percentages with two decimal points
            $terima_percentage = $total_rekap_hasil > 0 ? ($terima_count / $total_rekap_hasil) * 100 : 0;
            $revisi_percentage = $total_rekap_hasil > 0 ? ($revisi_count / $total_rekap_hasil) * 100 : 0;
            $tolak_percentage = $total_rekap_hasil > 0 ? ($tolak_count / $total_rekap_hasil) * 100 : 0;
            $v3_percentage = $total_rekap_hasil > 0 ? ($v3_count / $total_rekap_hasil) * 100 : 0;

            // Create the response structure with percentages
            $status_counts = [
                [
                    'total_rekap_hasil' => $total_rekap_hasil
                ],
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
                ],
                [
                    'status_rekap_hasil' => 'proses',
                    'jumlah' => $v3_count,
                    'persentase' => number_format($v3_percentage, 2)
                ]
            ];

            return $this->successResponseWithData(
                $status_counts,
                'Data Opd Provinsi berhasil diambil',
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
                ->map(function ($antarWaktu) {
                    // Group indikator by sektor and count them
                    $sektorCounts = $antarWaktu->indikator
                        ->groupBy('sektor.nama')
                        ->map(function ($indikators) {
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
                'Data Opd Provinsi berhasil diambil',
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
                return $this->errorResponse(
                    'opd_kabupaten_id is required',
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Retrieve the opd_provinsi_id from the OpdKabupaten model
            $opdKabupaten = OpdKabupaten::find($opd_kabupaten_id);
            if (!$opdKabupaten) {
                return $this->errorResponse(
                    'OpdKabupaten not found',
                    Response::HTTP_NOT_FOUND
                );
            }

            // Extract the opd_provinsi_id
            $opd_provinsi_id = $opdKabupaten->opd_provinsi_id;

            // Now proceed with the same logic using the retrieved opd_provinsi_id
            $total_variabel = Variabel::whereHas('indikator', function ($query) use ($opd_provinsi_id) {
                $query->where('opd_provinsi_id', $opd_provinsi_id);
            })->count();

            $total_indikator = Indikator::where('opd_provinsi_id', $opd_provinsi_id)->count();

            // Count the id from pivot_indikator_subjek where indikator_id is related to the opd_provinsi_id
            $total_subjek = PivotIndikatorSubjek::whereHas('indikator', function ($query) use ($opd_provinsi_id) {
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
                'Data Opd Kabupaten berhasil diambil',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function ChartDonutOpdKabupaten(Request $request)
    {
        try {
            // Check if opd_kabupaten_id is provided
            $opd_kabupaten_id = $request->input('opd_kabupaten_id');
            if (!$opd_kabupaten_id) {
                return $this->errorResponse(
                    'opd_kabupaten_id is required',
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Retrieve the opd_provinsi_id from the OpdKabupaten model
            $opdKabupaten = OpdKabupaten::find($opd_kabupaten_id);
            if (!$opdKabupaten) {
                return $this->errorResponse(
                    'OpdKabupaten not found',
                    Response::HTTP_NOT_FOUND
                );
            }

            // Extract the opd_provinsi_id
            $opd_provinsi_id = $opdKabupaten->opd_provinsi_id;

            // Count the total number of indikator filtered by opd_provinsi_id
            $total_rekap_hasil = RekapHasil::whereHas('periode', function ($query) use ($opd_provinsi_id) {
                $query->where('opd_provinsi_id', $opd_provinsi_id);
            })
                ->whereIn('status', ['v3', 'revisi', 'tolak', 'v2'])
                ->count();

            // Count the total number of each status in the RekapHasil model, filtered by opd_provinsi_id
            $terima_count = RekapHasil::whereHas('periode', function ($query) use ($opd_provinsi_id) {
                $query->where('opd_provinsi_id', $opd_provinsi_id);
            })->where('status', 'v3')->count();

            $revisi_count = RekapHasil::whereHas('periode', function ($query) use ($opd_provinsi_id) {
                $query->where('opd_provinsi_id', $opd_provinsi_id);
            })->where('status', 'revisi')->count();

            $tolak_count = RekapHasil::whereHas('periode', function ($query) use ($opd_provinsi_id) {
                $query->where('opd_provinsi_id', $opd_provinsi_id);
            })->where('status', 'tolak')->count();

            $proses_count = RekapHasil::whereHas('periode', function ($query) use ($opd_provinsi_id) {
                $query->where('opd_provinsi_id', $opd_provinsi_id);
            })->where('status', 'v2')->count();

            // Calculate percentages with two decimal points
            $terima_percentage = $total_rekap_hasil > 0 ? ($terima_count / $total_rekap_hasil) * 100 : 0;
            $revisi_percentage = $total_rekap_hasil > 0 ? ($revisi_count / $total_rekap_hasil) * 100 : 0;
            $tolak_percentage = $total_rekap_hasil > 0 ? ($tolak_count / $total_rekap_hasil) * 100 : 0;
            $proses_percentage = $total_rekap_hasil > 0 ? ($proses_count / $total_rekap_hasil) * 100 : 0;

            // Create the response structure with percentages
            $status_counts = [
                [
                    'total_rekap_hasil' => $total_rekap_hasil
                ],
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
                ],
                [
                    'status_rekap_hasil' => 'proses',
                    'jumlah' => $proses_count,
                    'persentase' => number_format($proses_percentage, 2)
                ]
            ];

            return $this->successResponseWithData(
                $status_counts,
                'Data Opd Kabupaten berhasil diambil',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function ChartBarOpdKabupaten(Request $request)
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
                return $this->errorResponse(
                    'OpdKabupaten not found',
                    Response::HTTP_NOT_FOUND
                );
            }

            // Extract the opd_provinsi_id
            $opd_provinsi_id = $opdKabupaten->opd_provinsi_id;

            // Query AntarWaktu with related Indikator and BeritaAcara
            $data = AntarWaktu::with(['indikator' => function ($query) use ($opd_provinsi_id) {
                // Filter indikator by opd_provinsi_id
                $query->where('opd_provinsi_id', $opd_provinsi_id);
            }, 'indikator.beritaAcara'])  // Add BeritaAcara relation
                ->get()
                ->map(function ($antarWaktu) {
                    // Calculate the total number of BeritaAcara across all indicators for each AntarWaktu
                    $jumlahBeritaAcara = $antarWaktu->indikator->sum(function ($indikator) {
                        return $indikator->beritaAcara->count();
                    });

                    return [
                        'tahun' => $antarWaktu->tahun,
                        'nama' => $antarWaktu->nama,
                        'jumlah_berita_acara' => $jumlahBeritaAcara  // Count of BeritaAcara
                    ];
                });

            // Build the response
            return response()->json([
                'code' => Response::HTTP_OK,
                'status' => true,
                'message' => 'success',
                'data' => $data
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json([
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'status' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function EnumeratorView(Request $request)
    {
        try {
            // Check if enumerator_id is provided
            $enumerator_id = $request->input('enumerator_id');
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
            $isInKabupaten = EnumeratorKabupaten::where('enumerator_id', $enumerator_id)->exists();
            $isInProvinsi = EnumeratorProvinsi::where('enumerator_id', $enumerator_id)->exists();

            $enumerator_kabupaten = EnumeratorKabupaten::find($enumerator_id);
            $enumerator_provinsi = EnumeratorProvinsi::find($enumerator_id);

            // If enumerator is not associated with either Kabupaten or Provinsi, return "no data"
            if (!$isInKabupaten && !$isInProvinsi) {
                return $this->successResponseWithData(
                    [],
                    'Data Enumerator tidak ditemukan',
                    Response::HTTP_OK
                );
            }

            // Initialize total_indikator count
            $total_indikator = RekapHasil::where('enumerator_id', $enumerator_id)->count();

            // Set totals based on where the enumerator is located
            if ($isInKabupaten) {
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
            } else { // If in EnumeratorProvinsi
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



    public function EnumeratorIndikator(Request $request)
    {
        try {
            // Validate that enumerator_id is provided
            $enumerator_id = $request->input('enumerator_id');
            if (!$enumerator_id) {
                return $this->errorResponse(
                    'enumerator_id is required',
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Retrieve data from Enumerator with Indikator details through the pivot table
            $data = Enumerator::with(['pivot_enumerator_indikator.indikator' => function ($query) {
                $query->select(['id', 'nama', 'sektor_id', 'opd_provinsi_id']);
            }])
                ->where('id', $enumerator_id)
                ->get()
                ->map(function ($enumerator) {
                    return $enumerator->pivot_enumerator_indikator->map(function ($pivot) {
                        return [
                            'id' => $pivot->indikator->id,
                            'nama_indikator' => $pivot->indikator->nama,
                            'logo' => $pivot->indikator->opd_provinsi->logo,
                            'sektor' => $pivot->indikator->sektor->nama,
                            'opd' => $pivot->indikator->opd_provinsi->nama,
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
