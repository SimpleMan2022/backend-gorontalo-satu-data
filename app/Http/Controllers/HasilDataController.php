<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{HasilData, Variabel, RekapHasil, Periode, Enumerator, OpdProvinsi, OpdKabupaten};
use App\Http\Resources\HasilDataResource;
use App\Http\Traits\ApiResponse;
use Exception;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Storage;
use Str;

class HasilDataController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 10;

            $hasilQuery = HasilData::orderBy('created_at', 'desc');

            if ($rekapHasilId = $request->query('rekap_hasil_id')) {
                $hasilQuery->where('rekap_hasil_id', $rekapHasilId);
            }

            if ($variabelId = $request->query('variabel_id')) {
                $hasilQuery->where('variabel_id', $variabelId);
            }

            $data = $hasilQuery->paginate($perPage);
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    HasilDataResource::collection($data),
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

    public function store(Request $request)
    {
        try {
            $now = date('Y-m-d');
            $periode = Periode::where('tanggal_mulai', '<=', $now)
                ->where('tanggal_selesai', '>=', $now)
                ->first();

            $enumeratorId = collect($request->all())->pluck('enumerator_id')->first();

            $enumerator = Enumerator::where('id', $enumeratorId)
                ->with('enumerator_kabupaten')
                ->with('enumerator_provinsi')
                ->first();

            if ($enumerator['enumerator_level'] == 'provinsi') {
                $opd = OpdProvinsi::where('id', $enumerator['enumerator_provinsi'][0]['id'])->first();
                $nama_rekap = $enumerator->nama . ' - ' . $opd->nama . ' (Provinsi) ' . ' - ' . $periode->nama;
            } else {
                $opd = OpdKabupaten::where('id', $enumerator['enumerator_kabupaten'][0]['id'])->first();
                $nama_rekap = $enumerator->nama . ' - ' . $opd->nama .  ' - ' . $periode->nama;
            }

            $rekap_hasil = RekapHasil::create([
                'nama' => $nama_rekap,
                'enumerator_id' => $enumerator->id ?? $enumeratorId,
                'periode_id' => $periode->id,
                'status' => 'v2',
            ]);

            $req = $request->validate([
                '*.variabel_id' => 'required|integer|exists:variabel,id',  // Validasi setiap 'variabel_id' di dalam array sebagai integer dan harus ada di tabel 'variabel'
                '*.konten' => 'required',  // Validasi setiap 'konten' di dalam array sebagai string
            ]);

            foreach ($req as $item) {

                // Buat data pada pivot jika belum ada
                $variabel = Variabel::find($item['variabel_id']);

                if ($variabel->tipe == 'gambar') {

                    $file = $item['konten'];


                    $randomString = rand(10000, 99999);
                    $enumerator_nama = strtolower(str_replace(' ', '_', $enumerator->nama));

                    $opd_nama = strtolower(str_replace(' ', '_', $opd->nama));

                    $file_name = $randomString . '_' . $enumerator_nama . '_' . $opd_nama . '.' . $file->getClientOriginalExtension();
                    $extension = $file->getClientOriginalExtension();

                    if (in_array($extension, ['jpg', 'png', 'jpeg'])) {
                        if ($file->getSize() <= 2048000) {
                            Storage::putFileAs('hasil_gambar', $file, $file_name);

                            $hasil_data = HasilData::create([
                                'rekap_hasil_id' => $rekap_hasil->id,
                                'variabel_id' => $item['variabel_id'],
                                'konten' => $file_name,
                            ]);
                        } else {
                            return $this->errorResponse('Ukuran file maksimal 2mb', Response::HTTP_BAD_REQUEST);
                        }
                    } else {
                        return $this->errorResponse('Ekstensi file harus berupa .jpg, .png atau .jpeg', Response::HTTP_BAD_REQUEST);
                    }
                }

                if ($variabel->tipe == 'file') {

                    $file = $item['konten'];

                    $randomString = rand(10000, 99999);
                    $enumerator_nama = strtolower(str_replace(' ', '_', $enumerator->nama));
                    $opd_nama = strtolower(str_replace(' ', '_', $opd->nama));

                    $file_name = $randomString . '_' . $enumerator_nama . '_' . $opd_nama . '.' . $file->getClientOriginalExtension();
                    $extension = $file->getClientOriginalExtension();

                    $extension = $file->getClientOriginalExtension();


                    if (in_array($extension, ['pdf'])) {
                        if ($file->getSize() <= 2048000) {
                            Storage::putFileAs('hasil_file', $file, $file_name);

                            $hasil_data = HasilData::create([
                                'rekap_hasil_id' => $rekap_hasil->id,
                                'variabel_id' => $item['variabel_id'],
                                'konten' => $file_name,
                            ]);
                        } else {
                            return $this->errorResponse('Ukuran file maksimal 2mb', Response::HTTP_BAD_REQUEST);
                        }
                    } else {
                        return $this->errorResponse('Ekstensi file harus berupa pdf', Response::HTTP_BAD_REQUEST);
                    }
                }
                if ($variabel->tipe != 'file' && $variabel->tipe != 'gambar') {
                    $hasil_data = HasilData::create([
                        'rekap_hasil_id' => $rekap_hasil->id,
                        'variabel_id' => $item['variabel_id'],
                        'konten' => $item['konten'],
                    ]);
                }
            }

            return $this->successResponse('Data berhasil dibuat', Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal dibuat', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(string $id)
    {
        try {
            $data = HasilData::find($id);

            // Mengecek apabila terdapat data dengan id yang sama
            if ($data) {
                return $this->successResponseWithData(
                    HasilDataResource::make($data),
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

    public function update(Request $request, $rekap_hasil_id)
    {
        try {
            $now = date('Y-m-d');
            $periode = Periode::where('tanggal_mulai', '<=', $now)
                ->where('tanggal_selesai', '>=', $now)
                ->first();

            $enumeratorId = collect($request->all())->pluck('enumerator_id')->first();

            $enumerator = Enumerator::where('id', $enumeratorId)
                ->with('enumerator_kabupaten')
                ->with('enumerator_provinsi')
                ->first();

            if ($enumerator['enumerator_level'] == 'provinsi') {
                $opd = OpdProvinsi::where('id', $enumerator['enumerator_provinsi'][0]['id'])->first();
                $nama_rekap = $enumerator->nama . ' - ' . $opd->nama . ' (Provinsi) ' . ' - ' . $periode->nama;
            } else {
                $opd = OpdKabupaten::where('id', $enumerator['enumerator_kabupaten'][0]['id'])->first();
                $nama_rekap = $enumerator->nama . ' - ' . $opd->nama .  ' - ' . $periode->nama;
            }

            $rekap_hasil = RekapHasil::find($rekap_hasil_id);

            $rekap_hasil->update([
                'nama' => $nama_rekap,
                'enumerator_id' => $enumeratorId ?? $rekap_hasil->enumerator_id,
                'periode_id' => $periode->id ?? $rekap_hasil->periode_id,
                'status' => 'v2',
            ]);


            $req = $request->validate([
                '*.variabel_id' => 'required|integer|exists:variabel,id',  // Validasi setiap 'variabel_id' di dalam array sebagai integer dan harus ada di tabel 'variabel'
                '*.konten' => 'required',  // Validasi setiap 'konten' di dalam array sebagai string
                '*.id' => 'required'
            ]);

            foreach ($req as $item) {

                // Buat data pada pivot jika belum ada
                $variabel = Variabel::find($item['variabel_id']);

                if ($variabel->tipe == 'gambar') {

                    $file = $item['konten'];


                    $randomString = rand(10000, 99999);
                    $enumerator_nama = strtolower(str_replace(' ', '_', $enumerator->nama));

                    $file_name = $randomString . '_' . $enumerator_nama . '_' . $opd->nama . '_' . $randomString . '.' . $file->getClientOriginalExtension();
                    $extension = $file->getClientOriginalExtension();

                    if (in_array($extension, ['jpg', 'png', 'jpeg'])) {
                        if ($file->getSize() <= 2048000) {
                            Storage::putFileAs('hasil_gambar', $file, $file_name);

                            HasilData::find($item['id'])->update([
                                'rekap_hasil_id' => $rekap_hasil->id,
                                'variabel_id' => $item['variabel_id'],
                                'konten' => $file_name,
                            ]);
                        } else {
                            return $this->errorResponse('Ukuran file maksimal 2mb', Response::HTTP_BAD_REQUEST);
                        }
                    } else {
                        return $this->errorResponse('Ekstensi file harus berupa .jpg, .png atau .jpeg', Response::HTTP_BAD_REQUEST);
                    }
                }

                if ($variabel->tipe == 'file') {

                    $file = $item['konten'];

                    $randomString = rand(10000, 99999);
                    $enumerator_nama = strtolower(str_replace(' ', '_', $enumerator->nama));


                    $file_name = $randomString . '_' . $enumerator_nama . '_' . $opd->nama . '_' . $randomString . '.' . $file->getClientOriginalExtension();

                    $extension = $file->getClientOriginalExtension();


                    if (in_array($extension, ['pdf'])) {
                        if ($file->getSize() <= 2048000) {
                            Storage::putFileAs('hasil_file', $file, $file_name);

                            HasilData::find($item['id'])->update([
                                'rekap_hasil_id' => $rekap_hasil->id,
                                'variabel_id' => $item['variabel_id'],
                                'konten' => $file_name,
                            ]);
                        } else {
                            return $this->errorResponse('Ukuran file maksimal 2mb', Response::HTTP_BAD_REQUEST);
                        }
                    } else {
                        return $this->errorResponse('Ekstensi file harus berupa pdf', Response::HTTP_BAD_REQUEST);
                    }
                }

                if ($variabel->tipe != 'file' && $variabel->tipe != 'gambar') {
                    HasilData::find($item['id'])->update([
                        'rekap_hasil_id' => $rekap_hasil->id,
                        'variabel_id' => $item['variabel_id'],
                        'konten' => $item['konten'],
                    ]);
                }
            }

            return $this->successResponse('Data berhasil diperbarui', Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal diperbarui', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function multiDestroy()
    {
        try {
            $id = request('id');
            $ids = explode(",", $id);

            $hasil_data = HasilData::whereIn('id', $ids)->pluck('id')->toArray();

            $notFoundIds = array_diff($ids, $hasil_data);

            if (!empty($notFoundIds)) {
                return $this->errorResponse('Data dengan ID berikut tidak ditemukan: ' . implode(', ', $notFoundIds), Response::HTTP_NOT_FOUND);
            }

            HasilData::whereIn('id', $hasil_data)->delete();

            return $this->successResponse('Hasil data berhasil dihapus', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
