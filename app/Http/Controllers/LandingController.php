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
                                return str_contains($hasil->konten, 'Kabupaten') || str_contains($hasil->konten, 'Kota');
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
