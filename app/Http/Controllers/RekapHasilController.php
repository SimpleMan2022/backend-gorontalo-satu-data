<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{HasilData, Variabel, RekapHasil, Periode, Enumerator, Indikator, OpdProvinsi, OpdKabupaten, PivotEnumeratorIndikator, Kabupaten};
use App\Http\Resources\RekapHasilResource;
use App\Http\Traits\ApiResponse;
use Exception;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Storage;
use Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RekapHasilController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            if (!$request->query('indikator_id')) {
                return $this->errorResponse('Parameter `indikator_id` tidak ditemukan', Response::HTTP_BAD_REQUEST);
            }
            $hasilQuery = RekapHasil::with(['indikator', 'enumerator', 'periode.opd_provinsi'])
                ->orderBy('created_at', 'desc');

            if ($enumeratorId = $request->query('enumerator_id')) {
                $hasilQuery->where('enumerator_id', $enumeratorId);
            }

            if ($periodeId = $request->query('periode_id')) {
                $hasilQuery->where('periode_id', $periodeId);
            }

            if ($indikatorId = $request->query('indikator_id')) {
                $hasilQuery->whereHas('indikator', function ($query) use ($indikatorId) {
                    $query->where('id', $indikatorId);
                });
            }


            $data = $hasilQuery->first();
            if ($data) {
                // Jika data ditemukan, format hasil seperti biasa

                $allHasilData = HasilData::with(['variabel', 'rekap_hasil'])
                    ->whereHas('rekap_hasil', function ($query) use ($request) {
                        if ($enumeratorId = $request->query('enumerator_id')) {
                            $query->where('enumerator_id', $enumeratorId);
                        }
                        if ($periodeId = $request->query('periode_id')) {
                            $query->where('periode_id', $periodeId);
                        }
                        $query->where('indikator_id', $request->query('indikator_id'));
                    })

                    ->orderBy('created_at', 'desc')
                    ->get();

                // Group hasil_data into pairs

                $groupedHasilData = $allHasilData->groupBy('rekap_hasil_id')->map(function ($items) {
                    return [
                        'id' => $items[0]->rekap_hasil->id,
                        'nama' => $items[0]->rekap_hasil->nama,
                        'status' => $items[0]->rekap_hasil->status,
                        'enumerator' => $items[0]->rekap_hasil->enumerator->only('id', 'nama', 'user_id'),
                        'wilayah_id' => $items[0]->rekap_hasil->wilayah_id,
                        'tipe_wilayah' => $items[0]->rekap_hasil->tipe_wilayah,
                        'hasil_data' => $items->map(function ($hasil) {
                            return [
                                'hasil_data_id' => $hasil->id,
                                'nama' => $hasil->variabel->nama,
                                'tipe' => $hasil->variabel->tipe,
                                'konten' => $hasil->konten,
                            ];
                        })->values()
                    ];
                })->values();

                // $groupedHasilData = $allHasilData->groupBy('rekap_hasil_id')->map(function ($items) {
                //     return $items->map(function ($hasil) {
                //         return [
                //             'hasil_data_id' => $hasil->id,
                //             'nama' => $hasil->variabel->nama,
                //             'tipe' => $hasil->variabel->tipe,
                //             'konten' => $hasil->konten,
                //         ];
                //     })->values();
                // })->values();

                // return $groupedHasilData;


                $formattedData = [
                    'id' => (int) $indikatorId,
                    'nama' => $data->hasil_data->first()->variabel->indikator->nama,
                    'nama_opd' => $data->periode->opd_provinsi->nama,
                    'subjek' => $data->subjek ? $data->subjek->map(function ($subjek) {
                        return [
                            'id' => $subjek->id,
                            'nama' => $subjek->nama,
                        ];
                    }) : [],
                    // 'enumerator' => [
                    //     'id' => $data->enumerator->id,
                    //     'nama' => $data->enumerator->nama,
                    //     'jabatan' => $data->enumerator->jabatan,
                    //     'enumerator_level' => $data->enumerator->enumerator_level,
                    // ],
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
                    'rekap_hasil' => $groupedHasilData,
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
                // dd('tes');
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
                            'subjek' => $indikator->subjek ? $indikator->subjek->map(function ($subjek) {
                                return [
                                    'id' => $subjek->id,
                                    'nama' => $subjek->nama,
                                ];
                            }) : [],
                            'enumerator' => $indikator->enumerators()->first() ? [
                                'id' => $indikator->enumerators()->first()->id,
                                'nama' => $indikator->enumerators()->first()->nama,
                                'jabatan' => $indikator->enumerators()->first()->jabatan,
                                'enumerator_level' => $indikator->enumerators()->first()->enumerator_level,
                            ] : null,
                            'periode' => [
                                'id' => $indikator->opd_provinsi()->first()->periode()->first()->id ?? null,
                                'nama' => $indikator->opd_provinsi()->first()->periode()->first()->nama ?? null,
                                'tanggal_mulai' => $indikator->opd_provinsi()->first()->periode()->first()->tanggal_mulai ?? null,
                                'tanggal_selesai' => $indikator->opd_provinsi()->first()->periode()->first()->tanggal_selesai ?? null,
                            ],
                            // 'periode' => $indikator->opd_provinsi()->first()->periode()->first()->nama ?? null,
                            'variabel' => $indikator->variabel->map(function ($variabel) {
                                return [
                                    'id' => $variabel->id,
                                    'nama' => $variabel->nama,
                                    'tipe' => $variabel->tipe,
                                ];
                            }),
                            'rekap_hasil' => [],
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

    public function rekapKeseluruhan(Request $request)
    {
        try {

            $hasilQuery = RekapHasil::with(['indikator', 'enumerator', 'periode.opd_provinsi']);
            if ($wilayahId = $request->query('wilayah_id')) {
                if ($wilayahId != 75) {
                    if ($hasilQuery->where('wilayah_id', $wilayahId)->count() == 0) {
                        return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
                    }
                    $wilayahData = [
                        'desa' => $hasilQuery->first()->tipe_wilayah === \App\Models\Desa::class
                            ? [
                                'id' => $hasilQuery->first()->wilayah->id,
                                'name' => $hasilQuery->first()->wilayah->name,
                            ]
                            : null,
                        'kecamatan' => $hasilQuery->first()->tipe_wilayah !== \App\Models\Kabupaten::class
                            ? [
                                'id' => $hasilQuery->first()->wilayah->kecamatan->id ?? $hasilQuery->first()->wilayah->id,
                                'name' => $hasilQuery->first()->wilayah->kecamatan->name ?? $hasilQuery->first()->wilayah->name,
                            ]
                            : null,
                        'kabupaten' => [
                            'id' => $hasilQuery->first()->tipe_wilayah === \App\Models\Desa::class
                                ? $hasilQuery->first()->wilayah->kecamatan->kabupaten->id
                                : ($hasilQuery->first()->tipe_wilayah === \App\Models\Kecamatan::class
                                    ? $hasilQuery->first()->wilayah->kabupaten->id
                                    : $hasilQuery->first()->wilayah->id),
                            'name' => $hasilQuery->first()->tipe_wilayah === \App\Models\Desa::class
                                ? $hasilQuery->first()->wilayah->kecamatan->kabupaten->name
                                : ($hasilQuery->first()->tipe_wilayah === \App\Models\Kecamatan::class
                                    ? $hasilQuery->first()->wilayah->kabupaten->name
                                    : $hasilQuery->first()->wilayah->name),
                        ],
                    ];
                } else {
                    $wilayahData = null;
                }
                if ($wilayahId == 75) {
                    // Kode Untuk Provinsi 
                    $rekapHasilQuery = DB::table('regencies as kabupaten')
                        ->leftJoin(DB::raw('(
                            SELECT 
                                LEFT(rekap_hasil.wilayah_id, 4) as kode_kabupaten,
                                variabel.nama,
                                SUM(hasil_data.konten) as total_nilai
                            FROM rekap_hasil
                            LEFT JOIN hasil_data ON rekap_hasil.id = hasil_data.rekap_hasil_id
                            LEFT JOIN indikator ON rekap_hasil.indikator_id = indikator.id
                            LEFT JOIN variabel ON hasil_data.variabel_id = variabel.id
                            WHERE variabel.tipe = "angka"
                            AND rekap_hasil.status = "terima"
                            AND rekap_hasil.indikator_id = :indikator_id  -- Menambahkan filter untuk indikator_id
                            GROUP BY kode_kabupaten, variabel.nama
                        ) as hasil_rekap'), 'kabupaten.id', '=', 'hasil_rekap.kode_kabupaten')
                        ->select('kabupaten.id as kode_kabupaten', 'kabupaten.name as nama_kabupaten', 'hasil_rekap.nama', DB::raw('IFNULL(hasil_rekap.total_nilai, 0) as total_nilai'))
                        ->orderBy('kabupaten.id');

                    $rekapHasilQuery = $rekapHasilQuery->addBinding($request->query('indikator_id'), 'select')->get();

                    // dd($rekapHasilQuery);   

                    $variabels = Variabel::where('indikator_id', $request->query('indikator_id'))->where('tipe', 'angka')->pluck('nama');

                    function addMissingVariables(&$result, $variabels)
                    {
                        // Menambahkan variabel yang hilang dengan nilai 0
                        foreach ($variabels as $variableName) {
                            if (!isset($result[$variableName])) {
                                $result[$variableName] = 0;
                            }
                        }
                    }

                    // Format data menjadi struktur array yang diinginkan
                    $formattedData = $rekapHasilQuery->groupBy('kode_kabupaten')->map(function ($items, $kode_kabupaten) use ($variabels) {

                        $result = [
                            'kode_wilayah' => $kode_kabupaten,
                            'nama_wilayah' => $items->first()->nama_kabupaten,
                        ];
                        foreach ($items as $item) {
                            if (!empty($item->nama)) {
                                $result[$item->nama] = $item->total_nilai;
                            }
                        }

                        addMissingVariables($result, $variabels);

                        return $result;
                    })->values()->toArray();

                    // return $formattedData;

                    $response = [
                        "indikator" => $hasilQuery->first()->indikator->nama,
                        "subjek" => $hasilQuery->first()->indikator->subjek->map(function ($subjek) {
                            return [
                                'id' => $subjek->id,
                                'nama' => $subjek->nama,
                            ];
                        }),
                        "wilayah" => $wilayahData,
                        "rekap" => $formattedData,
                    ];

                    return $this->successResponseWithData(
                        $response,
                        'Rekap hasil berhasil diambil',
                        Response::HTTP_OK
                    );
                } else if (strlen($wilayahId) == 4) {
                    // Kode Untuk yang Kabupaten

                    $rekapHasilQuery = DB::table('districts')
                        ->leftJoin(DB::raw('(
                            SELECT 
                                LEFT(rekap_hasil.wilayah_id, 7) as kode_kecamatan,
                                variabel.nama,
                                SUM(hasil_data.konten) as total_nilai
                            FROM rekap_hasil
                            LEFT JOIN hasil_data ON rekap_hasil.id = hasil_data.rekap_hasil_id
                            LEFT JOIN indikator ON rekap_hasil.indikator_id = indikator.id
                            LEFT JOIN variabel ON hasil_data.variabel_id = variabel.id
                            WHERE variabel.tipe = "angka" 
                            AND rekap_hasil.indikator_id = ?
                            AND rekap_hasil.status = "terima"
                            AND SUBSTR(rekap_hasil.wilayah_id, 1, 4) = ?
                            GROUP BY rekap_hasil.wilayah_id, variabel.nama
                        ) as hasil_rekap'), 'districts.id', '=', 'hasil_rekap.kode_kecamatan')
                        ->select('districts.id as kode_kecamatan', 'districts.name as nama_kecamatan', 'districts.regency_id as kode_kabupaten', 'hasil_rekap.nama', DB::raw('IFNULL(hasil_rekap.total_nilai, 0) as total_nilai'))
                        ->whereRaw('SUBSTR(districts.id, 1, 4) = ?', [substr($request->query('wilayah_id'), 0, 4)])
                        ->orderBy('districts.id');

                    // Add bindings for indikator_id and wilayah_id filter
                    $rekapHasilQuery = $rekapHasilQuery
                        ->addBinding($request->query('indikator_id'), 'select')
                        ->addBinding(substr($request->query('wilayah_id'), 0, 4), 'select')
                        ->get();

                    // dd($rekapHasilQuery);

                    // dd($rekapHasilQuery);

                    // Fetch variabel names for the specific indikator_id
                    $variabels = Variabel::where('indikator_id', $request->query('indikator_id'))
                        ->where('tipe', 'angka')
                        ->pluck('nama');

                    // Function to add missing variables with 0 total_nilai
                    function addMissingVariables(&$result, $variabels)
                    {
                        foreach ($variabels as $variabel) {
                            if (!array_key_exists($variabel, $result)) {
                                $result[$variabel] = 0; // Default value
                            }
                        }
                    }



                    // Format data to the desired structure
                    $formattedData = $rekapHasilQuery->groupBy(function ($item) {
                        return $item->kode_kecamatan ?? $item->nama_kecamatan; // Gunakan nama kecamatan jika kode null
                    })->map(function ($items, $kode_kecamatan) use ($variabels) {
                        // Inisialisasi data kecamatan
                        $firstItem = $items->first(); // Ambil elemen pertama
                        $result = [
                            'kode_wilayah' => $kode_kecamatan,
                            'nama_wilayah' => $firstItem->nama_kecamatan ?? null,
                            'kode_kabupaten' => $firstItem->kode_kabupaten ?? null,
                        ];

                        // Tambahkan nilai total untuk setiap variabel
                        foreach ($items as $item) {
                            if (!empty($item->nama)) {
                                $result[$item->nama] = $item->total_nilai;
                            }
                        }

                        // Tambahkan variabel yang hilang
                        addMissingVariables($result, $variabels);

                        return $result;
                    })->values()->toArray();
                    $response = [
                        "indikator" => $hasilQuery->first()->indikator->nama,
                        "subjek" => $hasilQuery->first()->indikator->subjek->map(function ($subjek) {
                            return [
                                'id' => $subjek->id,
                                'nama' => $subjek->nama,
                            ];
                        }),
                        "wilayah" => $wilayahData,
                        "rekap" => $formattedData,
                    ];
                    return $this->successResponseWithData(
                        $response,
                        'Rekap hasil berhasil diambil',
                        Response::HTTP_OK
                    );

                    // return $formattedData;

                } else if (strlen($wilayahId) == 7) {
                    // Kode untuk kecamatan

                    $rekapHasilQuery = DB::table('villages')
                        ->leftJoin(DB::raw('(
                            SELECT 
                                LEFT(rekap_hasil.wilayah_id, 10) as kode_desa,
                                variabel.nama,
                                SUM(hasil_data.konten) as total_nilai
                            FROM rekap_hasil
                            LEFT JOIN hasil_data ON rekap_hasil.id = hasil_data.rekap_hasil_id
                            LEFT JOIN indikator ON rekap_hasil.indikator_id = indikator.id
                            LEFT JOIN variabel ON hasil_data.variabel_id = variabel.id
                            WHERE variabel.tipe = "angka" 
                            AND rekap_hasil.indikator_id = ?
                            AND rekap_hasil.status = "terima"
                            AND SUBSTR(rekap_hasil.wilayah_id, 1, 7) = ?
                            GROUP BY rekap_hasil.wilayah_id, variabel.nama
                        ) as hasil_rekap'), 'villages.id', '=', 'hasil_rekap.kode_desa')
                        ->select('villages.id as kode_desa', 'villages.name as nama_desa', 'villages.district_id as kode_kecamatan', 'hasil_rekap.nama', DB::raw('IFNULL(hasil_rekap.total_nilai, 0) as total_nilai'))
                        ->whereRaw('SUBSTR(villages.id, 1, 7) = ?', [substr($request->query('wilayah_id'), 0, 7)])
                        ->orderBy('villages.id');

                    // Add bindings for indikator_id and wilayah_id filter
                    $rekapHasilQuery = $rekapHasilQuery
                        ->addBinding($request->query('indikator_id'), 'select')
                        ->addBinding(substr($request->query('wilayah_id'), 0, 7), 'select')
                        ->get();


                    // Fetch variabel names for the specific indikator_id
                    $variabels = Variabel::where('indikator_id', $request->query('indikator_id'))
                        ->where('tipe', 'angka')
                        ->pluck('nama');

                    // Function to add missing variables with 0 total_nilai
                    function addMissingVariables(&$result, $variabels)
                    {
                        foreach ($variabels as $variabel) {
                            if (!array_key_exists($variabel, $result)) {
                                $result[$variabel] = 0; // Default value
                            }
                        }
                    }

                    // Format data to the desired structure
                    $formattedData = $rekapHasilQuery->groupBy(function ($item) {
                        return $item->kode_desa ?? $item->nama_desa; // Gunakan nama desa jika kode null
                    })->map(function ($items, $kode_desa) use ($variabels) {
                        // Inisialisasi data desa
                        $firstItem = $items->first(); // Ambil elemen pertama
                        $result = [
                            'kode_wilayah' => $kode_desa,
                            'nama_wilayah' => $firstItem->nama_desa ?? null,
                            'kode_kecamatan' => $firstItem->kode_kecamatan ?? null,
                        ];

                        // Tambahkan nilai total untuk setiap variabel
                        foreach ($items as $item) {
                            if (!empty($item->nama)) {
                                $result[$item->nama] = $item->total_nilai;
                            }
                        }

                        // Tambahkan variabel yang hilang
                        addMissingVariables($result, $variabels);

                        return $result;
                    })->values()->toArray();
                    $response = [
                        "indikator" => $hasilQuery->first()->indikator->nama,
                        "subjek" => $hasilQuery->first()->indikator->subjek->map(function ($subjek) {
                            return [
                                'id' => $subjek->id,
                                'nama' => $subjek->nama,
                            ];
                        }),
                        "wilayah" => $wilayahData,
                        "rekap" => $formattedData,
                    ];
                    return $this->successResponseWithData(
                        $response,
                        'Rekap hasil berhasil diambil',
                        Response::HTTP_OK
                    );
                } else if (strlen($wilayahId) == 10) {
                    // Kode untuk Desa

                    $hasilQuery = RekapHasil::with(['indikator', 'enumerator', 'periode.opd_provinsi'])
                        ->orderBy('created_at', 'desc');

                    if ($indikatorId = $request->query('indikator_id')) {
                        $hasilQuery->whereHas('indikator', function ($query) use ($indikatorId) {
                            $query->where('id', $indikatorId);
                        });
                    }


                    $data = $hasilQuery->first();
                    if ($data) {
                        // Jika data ditemukan, format hasil seperti biasa

                        $allHasilData = HasilData::with(['variabel', 'rekap_hasil'])
                            ->whereHas('rekap_hasil', function ($query) use ($request) {
                                if ($enumeratorId = $request->query('enumerator_id')) {
                                    $query->where('enumerator_id', $enumeratorId);
                                }
                                if ($periodeId = $request->query('periode_id')) {
                                    $query->where('periode_id', $periodeId);
                                }
                                $query->where('indikator_id', $request->query('indikator_id'));
                            })

                            ->orderBy('created_at', 'desc')
                            ->get();

                        // Group hasil_data into pairs

                        $groupedHasilData = $allHasilData->groupBy('rekap_hasil_id')->map(function ($items) {
                            return [
                                'id' => $items[0]->rekap_hasil->id,
                                'nama' => $items[0]->rekap_hasil->nama,
                                'status' => $items[0]->rekap_hasil->status,
                                'enumerator' => $items[0]->rekap_hasil->enumerator->only('id', 'nama', 'user_id'),
                                'wilayah_id' => $items[0]->rekap_hasil->wilayah_id,
                                'tipe_wilayah' => $items[0]->rekap_hasil->tipe_wilayah,
                                'hasil_data' => $items->map(function ($hasil) {
                                    return [
                                        'hasil_data_id' => $hasil->id,
                                        'nama' => $hasil->variabel->nama,
                                        'tipe' => $hasil->variabel->tipe,
                                        'konten' => $hasil->konten,
                                    ];
                                })->values()
                            ];
                        })->values();

                        $formattedData = [
                            'id' => (int) $indikatorId,
                            'nama' => $data->hasil_data->first()->variabel->indikator->nama,
                            'nama_opd' => $data->periode->opd_provinsi->nama,
                            'subjek' => $data->subjek ? $data->subjek->map(function ($subjek) {
                                return [
                                    'id' => $subjek->id,
                                    'nama' => $subjek->nama,
                                ];
                            }) : [],
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
                            'rekap_hasil' => $groupedHasilData,
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
                        // dd('tes');
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
                                    'subjek' => $indikator->subjek ? $indikator->subjek->map(function ($subjek) {
                                        return [
                                            'id' => $subjek->id,
                                            'nama' => $subjek->nama,
                                        ];
                                    }) : [],
                                    'enumerator' => $indikator->enumerators()->first() ? [
                                        'id' => $indikator->enumerators()->first()->id,
                                        'nama' => $indikator->enumerators()->first()->nama,
                                        'jabatan' => $indikator->enumerators()->first()->jabatan,
                                        'enumerator_level' => $indikator->enumerators()->first()->enumerator_level,
                                    ] : null,
                                    'periode' => [
                                        'id' => $indikator->opd_provinsi()->first()->periode()->first()->id ?? null,
                                        'nama' => $indikator->opd_provinsi()->first()->periode()->first()->nama ?? null,
                                        'tanggal_mulai' => $indikator->opd_provinsi()->first()->periode()->first()->tanggal_mulai ?? null,
                                        'tanggal_selesai' => $indikator->opd_provinsi()->first()->periode()->first()->tanggal_selesai ?? null,
                                    ],
                                    // 'periode' => $indikator->opd_provinsi()->first()->periode()->first()->nama ?? null,
                                    'variabel' => $indikator->variabel->map(function ($variabel) {
                                        return [
                                            'id' => $variabel->id,
                                            'nama' => $variabel->nama,
                                            'tipe' => $variabel->tipe,
                                        ];
                                    }),
                                    'rekap_hasil' => [],
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
                    }
                }
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
            $data = $request->json()->all();
            $validated = Validator::make($data, [
                '*.id' => 'required|integer|exists:rekap_hasil,id',
                '*.status' => 'required|in:v2,v3,tolak,terima,revisi',
            ])->validate();

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
