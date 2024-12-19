<?php

namespace App\Http\Controllers;

use App\Http\Resources\HasilSpasialResource;
use App\Http\Resources\IndikatorLandingResource;
use App\Http\Resources\IndikatorResource;
use App\Http\Resources\SektorResource;
use App\Http\Resources\SektorSpasialResource;
use App\Http\Resources\OlahanSpasialResource;
use App\Models\Sektor;
use App\Http\Traits\ApiResponse;
use App\Models\AntarWaktu;
use App\Models\HasilData;
use App\Models\HasilSpasial;
use App\Models\Indikator;
use App\Models\OpdProvinsi;
use App\Models\SektorSpasial;
use App\Models\OlahanSpasial;
use App\Models\{
    RekapHasil,
    Desa,
    Kecamatan,
    Kabupaten,
    Variabel
};
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LandingController extends Controller
{
    use ApiResponse;
    public function getSektor(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 10;
            $data = Sektor::orderBy('created_at', 'desc');

            if ($perPage === 'all') {
                $data = $data->get();
            } else {
                $data = $data->paginate($perPage);
            }

            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    $perPage === 'all' ? $data : $data->items(),
                    'Data Sektor berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse('Data masih kosong', Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getAntarWaktu(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 10;
            $data = AntarWaktu::orderBy('created_at', 'desc');

            if ($perPage === 'all') {
                $data = $data->get();
            } else {
                $data = $data->paginate($perPage);
            }

            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    $perPage === 'all' ? $data : $data->items(),
                    'Data Antar Waktu berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse('Data masih kosong', Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getSektorSpasial(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 10;
            $data = SektorSpasial::orderBy('created_at', 'desc');

            if ($perPage === 'all') {
                $data = $data->get();
            } else {
                $data = $data->paginate($perPage);
            }

            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    $perPage === 'all' ? $data : $data->items(),
                    'Data Sektor Spasial berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse('Data masih kosong', Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function getIndikatorAndDataSearch(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 10;
            $data = Indikator::where('status', 'public')->orderBy('created_at', 'desc');
            if ($request->query('sektor_id')) {
                $data = $data->where('sektor_id', $request->sektor_id);
            }

            if ($request->query('opd_provinsi_id')) {
                $data = $data->where('opd_provinsi_id', $request->opd_provinsi_id);
            }
            if ($request->query('search')) {
                $data = $data->where('nama', 'like', '%' . $request->search . '%');
            }

            if ($request->query('antar_waktu')) {
                $data = $data->where('antar_waktu_id', $request->antar_waktu_id);
            }

            if ($perPage === 'all') {
                $data = $data->get();
            } else {
                $data = $data->paginate($perPage);
            }

            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    $perPage === 'all' ? IndikatorLandingResource::collection($data) : IndikatorLandingResource::collection($data->items()),
                    'Data Indikator berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse('Data masih kosong', Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getIndikator(Request $request, $id)
    {
        try {
            // Mencari data sektor dengan id
            $data = Indikator::with(['rekap_hasil.hasil_data', 'sektor', 'antar_waktu', 'opd_provinsi', 'subjek'])->where('id', $id)->first();

            if ($data) {
                // Proses per Kabupaten
                $perKabupaten = collect($data->rekap_hasil)
                    ->flatMap(function ($rekap) {
                        return collect($rekap->hasil_data)
                            ->filter(function ($hasil) {
                                return str_contains($hasil->variabel->tipe, 'kabupaten');
                            })
                            ->pluck('konten');
                    })
                    ->groupBy(function ($kabupaten) {
                        return $kabupaten;
                    })
                    ->map(function ($group) {
                        return $group->count();
                    });

                // Ambil semua HasilData terkait dengan rekap_hasil
                $allHasilData = HasilData::with(['variabel', 'rekap_hasil'])
                    ->whereHas('rekap_hasil', function ($query) use ($id) {
                        $query->where('indikator_id', $id);
                    })
                    ->orderBy('created_at', 'desc')
                    ->get();

                // Group hasil_data into pairs
                $groupedHasilData = $allHasilData->groupBy('rekap_hasil_id')->map(function ($items) {
                    return [
                        'id' => $items[0]->rekap_hasil->id,
                        'nama' => $items[0]->rekap_hasil->nama,
                        'status' => $items[0]->rekap_hasil->status,
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

                // Transformasi data
                $transformedData = [
                    'id' => $data->id,
                    'nama_indikator' => $data->nama,
                    'nama_sektor' => $data->sektor->nama,
                    'nama_opd' => $data->opd_provinsi->nama,
                    'logo_opd' => $data->opd_provinsi->logo,
                    'antar_waktu' => $data->antar_waktu->only('tahun', 'nama'),
                    'subjek' => $data->subjek->map(function ($subjek) {
                        return $subjek->only('nama');
                    }),
                    'variabel' => $data->variabel->map(function ($variabel) {
                        return [
                            'id' => $variabel->id,
                            'nama' => $variabel->nama,
                            'tipe' => $variabel->tipe,
                        ];
                    }),
                    'rekap_hasil' => $groupedHasilData,
                    'perkabupaten' => $perKabupaten,
                    'updated_at' => $data->updated_at,
                    'created_at' => $data->created_at,
                ];

                return $this->successResponseWithData(
                    $transformedData,
                    'Data indikator berhasil diambil',
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

    public function getOlahanSpasialandSearch(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 10;
            $data = OlahanSpasial::orderBy('created_at', 'desc');

            if ($request->query('sektor_spasial_id')) {
                $data = $data->where('sektor_spasial_id', $request->sektor_spasial_id);
            }

            if ($request->query('opd_provinsi_id')) {
                $data = $data->where('opd_provinsi_id', $request->opd_provinsi_id);
            }
            if ($request->query('search')) {
                $data = $data->where('judul', 'like', '%' . $request->search . '%');
            }

            if ($request->query('status')) {
                $data = $data->where('status', $request->status);
            }

            if ($perPage === 'all') {
                $data = $data->get();
            } else {
                $data = $data->paginate($perPage);
            }

            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    $perPage === 'all' ? OlahanSpasialResource::collection($data) : OlahanSpasialResource::collection($data->items()),
                    'Data Olahan Spasial berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse('Data masih kosong', Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getHasilSpasialandSearch(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 10;
            $data = HasilSpasial::orderBy('created_at', 'desc');

            if ($request->query('sektor_spasial_id')) {
                $data = $data->where('sektor_spasial_id', $request->sektor_spasial_id);
            }

            if ($request->query('opd_provinsi_id')) {
                $data = $data->where('opd_provinsi_id', $request->opd_provinsi_id);
            }
            if ($request->query('search')) {
                $data = $data->where('judul', 'like', '%' . $request->search . '%');
            }

            if ($request->query('status')) {
                $data = $data->where('status', $request->status);
            }

            if ($perPage === 'all') {
                $data = $data->get();
            } else {
                $data = $data->paginate($perPage);
            }

            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    $perPage === 'all' ? HasilSpasialResource::collection($data) : HasilSpasialResource::collection($data->items()),
                    'Data Hasil Spasial berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse('Data masih kosong', Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getRekapIndikator(Request $request)
    {
        try {

            $hasilQuery = RekapHasil::with(['indikator', 'enumerator', 'periode.opd_provinsi']);            
            
            if ($wilayahId = $request->query('wilayah_id')) {

                $wilayahData = [];
                function getWilayahData(&$wilayahData, $wilayahId) {
                    // dd($wilayahId);                    
                    if(strlen($wilayahId) == 4) {
                        $kabupaten = Kabupaten::where('id', $wilayahId)->first();
                        $wilayahData = [
                            'kabupaten' => [
                                'id' => (int)$wilayahId,
                                'name' => $kabupaten->name
                            ],
                            'provinsi' => [
                                'id' => 75,
                                'name' => 'Provinsi Gorontalo',
                            ]
                            ];
                    }else if(strlen($wilayahId) == 7) {
                        $kecamatan = Kecamatan::where('id', $wilayahId)->first();
                        $wilayahData = [
                            'kecamatan' => [
                                'id' => (int)$wilayahId,
                                'name' => $kecamatan->name
                            ],
                            'kabupaten' => [
                                'id' => $kecamatan->kabupaten->id,
                                'name' => $kecamatan->kabupaten->name
                            ],
                            'provinsi' => [
                                'id' => 75,
                                'name' => 'Provinsi Gorontalo',
                            ]
                            ];
                    }else if(strlen($wilayahId) == 10) {
                        $desa = Desa::where('id', $wilayahId)->first();
                        $wilayahData = [
                            'desa' => [
                                'id' => (int)$wilayahId,
                                'name' => $desa->name
                            ],
                            'kecamatan' => [
                                'id' => $desa->kecamatan->id,
                                'name' => $desa->kecamatan->name
                            ],
                            'kabupaten' => [
                                'id' => $desa->kecamatan->kabupaten->id,
                                'name' => $desa->kecamatan->kabupaten->name
                            ],
                            'provinsi' => [
                                'id' => 75,
                                'name' => 'Provinsi Gorontalo',
                            ]
                        ];
                    }                    
                }
                getWilayahData($wilayahData, $wilayahId);

                

                if ($wilayahId != 75) {        

                    getWilayahData($wilayahData, $wilayahId);
                    
                } else {
                    $wilayahData = [
                        'provinsi' => [
                            'id' => (int)$wilayahId,
                            'name' => 'Provinsi Gorontalo',
                        ]
                    ];
                }
                if ($wilayahId == 75) {

                    // Cek jika indikator tingkatan provinsi
                    if($hasilQuery->first()->indikator->tingkatan == 'provinsi') {
                        $hasilQuery = RekapHasil::with(['indikator', 'enumerator', 'periode.opd_provinsi'])
                            ->where('wilayah_id', $request->query('wilayah_id'))
                            ->whereIn('status', ['terima', 'v3'])
                            ->orderBy('created_at', 'desc');

                            
                        if ($indikatorId = $request->query('indikator_id')) {
                            $hasilQuery->whereHas('indikator', function ($query) use ($indikatorId) {
                                $query->where('id', $indikatorId)->where('status','public');
                            });
                        }

                        // return $hasilQuery->first();


                        $data = $hasilQuery->first();

 
                        
                        // dd($data);
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

                            $groupedHasilData = $allHasilData->groupBy('rekap_hasil_id')->map(function ($items) {
                                $rekapHasil = $items[0]->rekap_hasil; // Data utama dari rekap_hasil
                            
                                // Tambahkan key `hasil_data` sebagai properti individual
                                $additionalData = $items->mapWithKeys(function ($hasil) {
                                    return [
                                        $hasil->variabel->nama => $hasil->konten, // Nama variabel sebagai key, konten sebagai value
                                    ];
                                });
                            
                                return array_merge([                                    
                                    'kode_wilayah' => $rekapHasil->wilayah_id,
                                    'nama_wilayah' => $rekapHasil->wilayah->name,                                    
                                ], $additionalData->toArray()); // Gabungkan data utama dengan hasil_data
                            })->values();

                            

                            $formattedData = [
                                'id' => (int) $indikatorId,
                                'indikator' => [
                                    'id' => $data->hasil_data->first()->variabel->indikator->id,
                                    'nama' => $data->hasil_data->first()->variabel->indikator->nama,
                                    'tingkatan' => $data->hasil_data->first()->variabel->indikator->tingkatan,
                                    'status' => $data->hasil_data->first()->variabel->indikator->status,
                                ],

                                'subjek' => $data->subjek ? $data->subjek->map(function ($subjek) {
                                    return [
                                        'id' => $subjek->id,
                                        'nama' => $subjek->nama,
                                    ];
                                }) : [],
                                'wilayah' => $wilayahData,                               
                                'variabel' => $data->hasil_data->map(function ($hasil) {
                                    return [
                                        'id' => $hasil->variabel->id,
                                        'nama' => $hasil->variabel->nama,
                                        'tipe' => $hasil->variabel->tipe,
                                    ];
                                }),
                                'rekap' => $groupedHasilData,
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

                                // dd($wilayahData);
                                if ($indikator) {
                                    // Format data tanpa `hasil_data`
                                    $formattedData = [
                                        'id' => (int) $indikatorId,
                                        'indikator' => [
                                            'id' => $data->hasil_data->first()->variabel->indikator->id,
                                            'nama' => $data->hasil_data->first()->variabel->indikator->nama,
                                            'tingkatan' => $data->hasil_data->first()->variabel->indikator->tingkatan,
                                            'status' => $data->hasil_data->first()->variabel->indikator->status,
                                        ],
                                        // 'nama_opd' => $indikator->opd_provinsi->nama,
                                        'subjek' => $indikator->subjek ? $indikator->subjek->map(function ($subjek) {
                                            return [
                                                'id' => $subjek->id,
                                                'nama' => $subjek->nama,
                                            ];
                                        }) : [],
                                        'wilayah' => $wilayahData,                                   
                                        'variabel' => $indikator->variabel->map(function ($variabel) {
                                            return [
                                                'id' => $variabel->id,
                                                'nama' => $variabel->nama,
                                                'tipe' => $variabel->tipe,
                                            ];
                                        }),
                                        'rekap' => [],
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
                    }else {
                        
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
                                AND rekap_hasil.status IN ("terima", "v3")
                                AND rekap_hasil.indikator_id = :indikator_id  -- Menambahkan filter untuk indikator_id
                                GROUP BY kode_kabupaten, variabel.nama
                            ) as hasil_rekap'), 'kabupaten.id', '=', 'hasil_rekap.kode_kabupaten')
                            ->select('kabupaten.id as kode_kabupaten', 'kabupaten.name as nama_kabupaten', 'hasil_rekap.nama', DB::raw('IFNULL(hasil_rekap.total_nilai, 0) as total_nilai'))
                            ->orderBy('kabupaten.id');

                        $rekapHasilQuery = $rekapHasilQuery->addBinding($request->query('indikator_id'), 'select')->get();

                        // dd($rekapHasilQuery);   

                        $variabels = Variabel::where('indikator_id', $request->query('indikator_id'))->where('tipe', 'angka');

                        if($variabels->pluck('nama')->isEmpty()) {
                            return $this->errorResponse('Indikator tidak ditemukan', Response::HTTP_NOT_FOUND);
                        }

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

                            addMissingVariables($result, $variabels->pluck('nama'));

                            return $result;
                        })->values()->toArray();

                        if($variabels->first()->indikator->status == 'public') {
                            $response = [
                                "indikator" => [
                                    "id" => $variabels->first()->indikator->id, 
                                    "nama" => $variabels->first()->indikator->nama,
                                    "tingkatan" => $variabels->first()->indikator->tingkatan,
                                    "status" => $variabels->first()->indikator->status
                                ],
                                "subjek" => $variabels->first()->indikator->subjek->map(function ($subjek) {
                                    return [
                                        'id' => $subjek->id,
                                        'nama' => $subjek->nama,
                                    ];
                                }),
                                "variabel" => $variabels->select('id', 'nama', 'tipe')->get(),
                                "wilayah" => $wilayahData,
                                "rekap" => $formattedData,
                            ];
    
                            return $this->successResponseWithData(
                                $response,
                                'Rekap hasil berhasil diambil',
                                Response::HTTP_OK
                            );
                        }else {
                            return $this->errorResponse('Indikator tidak ditemukan', Response::HTTP_NOT_FOUND);
                        }
                    }
                } else if (strlen($wilayahId) == 4) {

                    // Cek jika indikator tingkatan kabupaten
                    if($hasilQuery->first()->indikator->tingkatan == 'kabupaten') {
                        $hasilQuery = RekapHasil::with(['indikator', 'enumerator', 'periode.opd_provinsi'])
                            ->where('wilayah_id', $request->query('wilayah_id'))
                            ->whereIn('status', ['terima', 'v3'])
                            ->orderBy('created_at', 'desc');

                            
                        if ($indikatorId = $request->query('indikator_id')) {
                            $hasilQuery->whereHas('indikator', function ($query) use ($indikatorId) {
                                $query->where('id', $indikatorId)->where('status','public');
                            });
                        }

                        // return $hasilQuery->first();


                        $data = $hasilQuery->first();
                        // dd($data);
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

                            $groupedHasilData = $allHasilData->groupBy('rekap_hasil_id')->map(function ($items) {
                                $rekapHasil = $items[0]->rekap_hasil; // Data utama dari rekap_hasil
                            
                                // Tambahkan key `hasil_data` sebagai properti individual
                                $additionalData = $items->mapWithKeys(function ($hasil) {
                                    return [
                                        $hasil->variabel->nama => $hasil->konten, // Nama variabel sebagai key, konten sebagai value
                                    ];
                                });
                            
                                return array_merge([
                                    // 'id' => $rekapHasil->id,
                                    'kode_wilayah' => $rekapHasil->wilayah_id,
                                    'nama_wilayah' => $rekapHasil->wilayah->name,
                                    // 'nama' => $rekapHasil->nama,
                                    // 'status' => $rekapHasil->status,
                                    // 'enumerator' => $rekapHasil->enumerator->only('id', 'nama', 'user_id'),
                                    // 'wilayah_id' => $rekapHasil->wilayah_id,
                                    // 'tipe_wilayah' => $rekapHasil->tipe_wilayah,
                                ], $additionalData->toArray()); // Gabungkan data utama dengan hasil_data
                            })->values();

                            $formattedData = [
                                'id' => (int) $indikatorId,
                                'indikator' => [
                                    'id' => $data->hasil_data->first()->variabel->indikator->id, 
                                    'nama' => $data->hasil_data->first()->variabel->indikator->nama,
                                    'tingkatan' => $data->hasil_data->first()->variabel->indikator->tingkatan,
                                    'status' => $data->hasil_data->first()->variabel->indikator->status
                                ],
                                // 'nama_opd' => $data->periode->opd_provinsi->nama,
                                'subjek' => $data->subjek ? $data->subjek->map(function ($subjek) {
                                    return [
                                        'id' => $subjek->id,
                                        'nama' => $subjek->nama,
                                    ];
                                }) : [],
                                'wilayah' => $wilayahData,
                                // 'periode' => [
                                //     'id' => $data->periode->id,
                                //     'nama' => $data->periode->nama,
                                //     'tanggal_mulai' => $data->periode->tanggal_mulai,
                                //     'tanggal_selesai' => $data->periode->tanggal_selesai,
                                // ],
                                'variabel' => $data->hasil_data->map(function ($hasil) {
                                    return [
                                        'id' => $hasil->variabel->id,
                                        'nama' => $hasil->variabel->nama,
                                        'tipe' => $hasil->variabel->tipe,
                                    ];
                                }),
                                'rekap' => $groupedHasilData,
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
                                    ->where('status','public')
                                    ->first();

                                // dd($wilayahData);
                                if ($indikator) {
                                    // Format data tanpa `hasil_data`
                                    $formattedData = [
                                        'id' => (int) $indikatorId,
                                        'indikator' => [
                                            'id' => $indikator->id, 
                                            'nama' => $indikator->nama,
                                            'tingkatan' => $indikator->tingkatan
                                        ],
                                        // 'nama_opd' => $indikator->opd_provinsi->nama,
                                        'subjek' => $indikator->subjek ? $indikator->subjek->map(function ($subjek) {
                                            return [
                                                'id' => $subjek->id,
                                                'nama' => $subjek->nama,
                                            ];
                                        }) : [],
                                        'wilayah' => $wilayahData,                                   
                                        'variabel' => $indikator->variabel->map(function ($variabel) {
                                            return [
                                                'id' => $variabel->id,
                                                'nama' => $variabel->nama,
                                                'tipe' => $variabel->tipe,
                                            ];
                                        }),
                                        'rekap' => [],
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
                    }else {

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
                                AND rekap_hasil.status IN ("terima", "v3")
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
    
                        // Fetch variabel names for the specific indikator_id
                        $variabels = Variabel::where('indikator_id', $request->query('indikator_id'))
                            ->where('tipe', 'angka');
                            
    
                        // Cek apakah variabel indikator kosong
                        if($variabels->pluck('nama')->isEmpty()) {
                            return $this->errorResponse('Indikator tidak ditemukan', Response::HTTP_NOT_FOUND);
                        }
    
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
                                // 'kode_kabupaten' => $firstItem->kode_kabupaten ?? null,
                            ];
    
                            // Tambahkan nilai total untuk setiap variabel
                            foreach ($items as $item) {
                                if (!empty($item->nama)) {
                                    $result[$item->nama] = $item->total_nilai;
                                }
                            }
    
                            // Tambahkan variabel yang hilang
                            addMissingVariables($result, $variabels->pluck('nama'));
    
                            return $result;
                        })->values()->toArray();

                        if($variabels->first()->indikator->status == 'public') {
                            $response = [
                                'indikator' => [
                                    'id' => $variabels->first()->indikator->id, 
                                    'nama' => $variabels->first()->indikator->nama,
                                    'tingkatan' => $variabels->first()->indikator->tingkatan,
                                    'status' => $variabels->first()->indikator->status
                                ],
                                "subjek" => $variabels->first()->indikator->subjek->map(function ($subjek) {
                                    return [
                                        'id' => $subjek->id,
                                        'nama' => $subjek->nama,
                                    ];
                                }),
                                "variabel" => $variabels->select('id', 'nama', 'tipe')->get(),
                                "wilayah" => $wilayahData,
                                "rekap" => $formattedData,
                            ];
                            return $this->successResponseWithData(
                                $response,
                                'Rekap hasil berhasil diambil',
                                Response::HTTP_OK
                            );
                        }else {
                            return $this->errorResponse('Indikator tidak ditemukan', Response::HTTP_NOT_FOUND);
                        }
                    }

                } else if (strlen($wilayahId) == 7) {

                    // Cek jika indikator tingkatan kecamatan
                    if($hasilQuery->first()->indikator->tingkatan == 'kecamatan') {
                        $hasilQuery = RekapHasil::with(['indikator', 'enumerator', 'periode.opd_provinsi'])
                            ->where('wilayah_id', $request->query('wilayah_id'))
                            ->whereIn('status', ['terima', 'v3'])
                            ->orderBy('created_at', 'desc');

                            
                        if ($indikatorId = $request->query('indikator_id')) {
                            $hasilQuery->whereHas('indikator', function ($query) use ($indikatorId) {
                                $query->where('id', $indikatorId)->where('status', 'public');
                            });
                        }


                        $data = $hasilQuery->first();
                        // dd($data);
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

                            $groupedHasilData = $allHasilData->groupBy('rekap_hasil_id')->map(function ($items) {
                                $rekapHasil = $items[0]->rekap_hasil; // Data utama dari rekap_hasil
                            
                                // Tambahkan key `hasil_data` sebagai properti individual
                                $additionalData = $items->mapWithKeys(function ($hasil) {
                                    return [
                                        $hasil->variabel->nama => $hasil->konten, // Nama variabel sebagai key, konten sebagai value
                                    ];
                                });
                            
                                return array_merge([
                                    // 'id' => $rekapHasil->id,
                                    'kode_wilayah' => $rekapHasil->wilayah_id,
                                    'nama_wilayah' => $rekapHasil->wilayah->name,
                                    // 'nama' => $rekapHasil->nama,
                                    // 'status' => $rekapHasil->status,
                                    // 'enumerator' => $rekapHasil->enumerator->only('id', 'nama', 'user_id'),
                                    // 'wilayah_id' => $rekapHasil->wilayah_id,
                                    // 'tipe_wilayah' => $rekapHasil->tipe_wilayah,
                                ], $additionalData->toArray()); // Gabungkan data utama dengan hasil_data
                            })->values();

                            $formattedData = [
                                'id' => (int) $indikatorId,
                                'indikator' => [
                                    'id' => $data->hasil_data->first()->variabel->indikator->id,
                                    'nama' => $data->hasil_data->first()->variabel->indikator->nama,
                                    'tingkatan' => $data->hasil_data->first()->variabel->indikator->tingkatan,
                                    'status' => $data->hasil_data->first()->variabel->indikator->status,
                                ],
                                // 'nama_opd' => $data->periode->opd_provinsi->nama,
                                'subjek' => $data->subjek ? $data->subjek->map(function ($subjek) {
                                    return [
                                        'id' => $subjek->id,
                                        'nama' => $subjek->nama,
                                    ];
                                }) : [],
                                'wilayah' => $wilayahData,
                                // 'periode' => [
                                //     'id' => $data->periode->id,
                                //     'nama' => $data->periode->nama,
                                //     'tanggal_mulai' => $data->periode->tanggal_mulai,
                                //     'tanggal_selesai' => $data->periode->tanggal_selesai,
                                // ],
                                'variabel' => $data->hasil_data->map(function ($hasil) {
                                    return [
                                        'id' => $hasil->variabel->id,
                                        'nama' => $hasil->variabel->nama,
                                        'tipe' => $hasil->variabel->tipe,
                                    ];
                                }),
                                'rekap' => $groupedHasilData,
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
                                    ->where('status','public')
                                    ->first();

                                // dd($wilayahData);
                                if ($indikator) {
                                    // Format data tanpa `hasil_data`
                                    $formattedData = [
                                        'id' => (int) $indikatorId,
                                        'indikator' => [
                                            'id' => $indikator->id, 
                                            'nama' => $indikator->nama, 
                                            'tingkatan' => $indikator->tingkatan
                                        ],
                                        // 'nama_opd' => $indikator->opd_provinsi->nama,
                                        'subjek' => $indikator->subjek ? $indikator->subjek->map(function ($subjek) {
                                            return [
                                                'id' => $subjek->id,
                                                'nama' => $subjek->nama,
                                            ];
                                        }) : [],
                                        'wilayah' => $wilayahData,                                   
                                        'variabel' => $indikator->variabel->map(function ($variabel) {
                                            return [
                                                'id' => $variabel->id,
                                                'nama' => $variabel->nama,
                                                'tipe' => $variabel->tipe,
                                            ];
                                        }),
                                        'rekap' => [],
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
                    }else {


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
                                AND rekap_hasil.status IN ("terima", "v3")
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
                            ->where('tipe', 'angka');
                            // ->pluck('nama');

                        // Cek apakah variabel indikator kosong
                        if($variabels->pluck('nama')->isEmpty()) {
                            return $this->errorResponse('Indikator tidak ditemukan', Response::HTTP_NOT_FOUND);
                        }

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
                                // 'kode_kecamatan' => $firstItem->kode_kecamatan ?? null,
                            ];

                            // Tambahkan nilai total untuk setiap variabel
                            foreach ($items as $item) {
                                if (!empty($item->nama)) {
                                    $result[$item->nama] = $item->total_nilai;
                                }
                            }

                            // Tambahkan variabel yang hilang
                            addMissingVariables($result, $variabels->pluck('nama'));

                            return $result;
                        })->values()->toArray();

                        if($variabels->first()->indikator->status == 'public') {

                            $response = [
                                "indikator" => [
                                    "id" => $variabels->first()->indikator->id, 
                                    "nama" => $variabels->first()->indikator->nama,
                                    "tingkatan" => $variabels->first()->indikator->tingkatan
                                ],
                                "subjek" => $variabels->first()->indikator->subjek->map(function ($subjek) {
                                    return [
                                        'id' => $subjek->id,
                                        'nama' => $subjek->nama,
                                    ];
                                }),
                                "wilayah" => $wilayahData,
                                "variabel" => $variabels->select('id', 'nama', 'tipe')->get(),
                                "rekap" => $formattedData,
                            ];
                            return $this->successResponseWithData(
                                $response,
                                'Rekap hasil berhasil diambil',
                                Response::HTTP_OK
                            );
                        }else {
                            return $this->errorResponse('Indikator tidak ditemukan', Response::HTTP_NOT_FOUND);
                        }
                    }
                } else if (strlen($wilayahId) == 10) {
                    // Kode untuk Desa

                    $hasilQuery = RekapHasil::with(['indikator', 'enumerator', 'periode.opd_provinsi'])
                        ->where('wilayah_id', $request->query('wilayah_id'))
                        ->whereIn('status', ['terima', 'v3'])
                        ->orderBy('created_at', 'desc');

                    if ($indikatorId = $request->query('indikator_id')) {
                        $hasilQuery->whereHas('indikator', function ($query) use ($indikatorId) {
                            $query->where('id', $indikatorId)->where('status', 'public');
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

                        $groupedHasilData = $allHasilData->groupBy('rekap_hasil_id')->map(function ($items) {
                            $rekapHasil = $items[0]->rekap_hasil; // Data utama dari rekap_hasil
                        
                            // Tambahkan key `hasil_data` sebagai properti individual
                            $additionalData = $items->mapWithKeys(function ($hasil) {
                                return [
                                    $hasil->variabel->nama => $hasil->konten, // Nama variabel sebagai key, konten sebagai value
                                ];
                            });
                        
                            return array_merge([
                                // 'id' => $rekapHasil->id,
                                'kode_wilayah' => $rekapHasil->wilayah_id,
                                'nama_wilayah' => $rekapHasil->wilayah->name,
                                // 'nama' => $rekapHasil->nama,
                                // 'status' => $rekapHasil->status,
                                // 'enumerator' => $rekapHasil->enumerator->only('id', 'nama', 'user_id'),
                                // 'wilayah_id' => $rekapHasil->wilayah_id,
                                // 'tipe_wilayah' => $rekapHasil->tipe_wilayah,
                            ], $additionalData->toArray()); // Gabungkan data utama dengan hasil_data
                        })->values();

                        $formattedData = [
                            'id' => (int) $indikatorId,
                            'indikator' => [
                                'id' => $data->hasil_data->first()->variabel->indikator->id, 
                                'nama' => $data->hasil_data->first()->variabel->indikator->nama,
                                'tingkatan' => $data->hasil_data->first()->variabel->indikator->tingkatan,
                                'status' => $data->hasil_data->first()->variabel->indikator->status,
                            ],
                            // 'nama_opd' => $data->periode->opd_provinsi->nama,
                            'subjek' => $data->subjek ? $data->subjek->map(function ($subjek) {
                                return [
                                    'id' => $subjek->id,
                                    'nama' => $subjek->nama,
                                ];
                            }) : [],
                            'wilayah' => $wilayahData,
                            // 'periode' => [
                            //     'id' => $data->periode->id,
                            //     'nama' => $data->periode->nama,
                            //     'tanggal_mulai' => $data->periode->tanggal_mulai,
                            //     'tanggal_selesai' => $data->periode->tanggal_selesai,
                            // ],
                            'variabel' => $data->hasil_data->map(function ($hasil) {
                                return [
                                    'id' => $hasil->variabel->id,
                                    'nama' => $hasil->variabel->nama,
                                    'tipe' => $hasil->variabel->tipe,
                                ];
                            }),
                            'rekap' => $groupedHasilData,
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
                                ->where('status','public')
                                ->first();

                            // dd($wilayahData);
                            if ($indikator) {
                                // Format data tanpa `hasil_data`
                                $formattedData = [
                                    'id' => (int) $indikatorId,
                                    'indikator' => [
                                        'id' => $indikator->id, 
                                        'nama' => $indikator->nama, 
                                        'tingkatan' => $indikator->tingkatan,
                                        'status' => $indikator->status,
                                    ],
                                    // 'nama_opd' => $indikator->opd_provinsi->nama,
                                    'subjek' => $indikator->subjek ? $indikator->subjek->map(function ($subjek) {
                                        return [
                                            'id' => $subjek->id,
                                            'nama' => $subjek->nama,
                                        ];
                                    }) : [],
                                    'wilayah' => $wilayahData,                                   
                                    'variabel' => $indikator->variabel->map(function ($variabel) {
                                        return [
                                            'id' => $variabel->id,
                                            'nama' => $variabel->nama,
                                            'tipe' => $variabel->tipe,
                                        ];
                                    }),
                                    'rekap' => [],
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

    public function getOlahanSpasial(Request $request, $id)
    {
        try {
            // Mencari data sektor dengan id
            $data = OlahanSpasial::with(['sektor_spasial', 'opd_provinsi', 'spasial_kabupaten'])->where('id', $id)->first();

            if ($data) {
                $group = [
                    'id' => $data->id,
                    'judul' => $data->judul,
                    'deskripsi' => $data->konten,
                    'spasial_kabupaten' => $data->spasial_kabupaten->map(function ($item) {
                        return (object) [
                            'id' => $item->id,
                            'tahun' => $item->tahun,
                            'konten' => $item->konten,
                            'kabupaten_id' => $item->kabupaten->id,
                            'kabupaten' => $item->kabupaten->name,
                        ];
                    }),
                    'sektor' => $data->sektor_spasial->sektor->only('id', 'nama'),
                    'sektor_spasial' => $data->sektor_spasial->only('id', 'nama'),
                    'opd_provinsi' => $data->opd_provinsi->only('id', 'nama'),
                    'status' => $data->status,
                    'created_at' => $data->created_at,
                    'updated_at' => $data->updated_at
                ];
                return $this->successResponseWithData(
                    $group,
                    'Data hasil spasial berhasil diambil',
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

    public function getHasilSpasial(Request $request, $id)
    {
        try {
            // Mencari data sektor dengan id
            $data = HasilSpasial::with(['sektor_spasial', 'opd_provinsi'])->where('id', $id)->first();

            if ($data) {
                $group = [
                    'id' => $data->id,
                    'judul' => $data->judul,
                    'deskripsi' => $data->konten,
                    'file' => $data->file,
                    'sektor' => $data->sektor_spasial->sektor->only('id', 'nama'),
                    'sektor_spasial' => $data->sektor_spasial->only('id', 'nama'),
                    'opd_provinsi' => $data->opd_provinsi->only('id', 'nama'),
                    'status' => $data->status,
                    'created_at' => $data->created_at,
                    'updated_at' => $data->updated_at
                ];
                return $this->successResponseWithData(
                    $group,
                    'Data hasil spasial berhasil diambil',
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

    public function getOpdProvinsi(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 10;
            $data = OpdProvinsi::orderBy('created_at', 'desc');

            if ($perPage === 'all') {
                $data = $data->get();
            } else {
                $data = $data->paginate($perPage);
            }

            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    $perPage === 'all' ? $data : $data->items(),
                    'Data OPD Provinsi berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse('Data masih kosong', Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
