<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Subjek, PivotIndikatorSubjek};
// use App\Http\Resources\SubjekResource;
use App\Http\Traits\ApiResponse;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;

class SubjekController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $data = Subjek::orderBy('created_at', 'desc')->get();
            if ($data->isNotEmpty()) {
                return $this->successResponseWithData(
                    $data,
                    'success',
                    Response::HTTP_OK
                );
            } else {
                return response()->json([
                    'message' => 'Data masih kosong.'
                ]);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function multiStore(Request $request)
    {
        try {
            $req = $request->validate([
                '*.nama' => 'required|string|max:255',  // Validasi setiap 'nama' di dalam array sebagai string
                '*.indikator_id' => 'required|integer|exists:indikator,id',  // Validasi setiap 'indikator_id' di dalam array sebagai integer dan harus ada di tabel 'indikators'
            ]);

            foreach ($req as $item) {

                // Cek apakah subjek sudah ada berdasarkan nama
                $subjek = Subjek::where('nama', $item['nama'])->first();

                // // Jika subjek belum ada, maka buat baru
                if (!$subjek) {
                    $subjek = Subjek::create([
                        'nama' => $item['nama'],
                        // tambahkan field lain sesuai kebutuhan
                    ]);
                }

                // Buat data pada pivot jika belum ada
                $pivot = PivotIndikatorSubjek::firstOrCreate([
                    'indikator_id' => $item['indikator_id'],
                    'subjek_id' => $subjek->id,
                ]);
            }

            return $this->successResponse(
                'success',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            $req = $request->validate([
                'nama' => 'required|string|max:255',  // Validasi setiap 'nama' di dalam array sebagai string
                'indikator_id' => 'required|integer|exists:indikator,id',  // Validasi setiap 'indikator_id' di dalam array sebagai integer dan harus ada di tabel 'indikators'
            ]);

            $subjek = Subjek::where('nama', $request->nama)->first();

            // // Jika subjek belum ada, maka buat baru
            if (!$subjek) {
                $subjek = Subjek::create([
                    'nama' => $request->nama,
                ]);

                return $this->successResponse(
                    'success',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse(
                    'failed',
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        try {
            // Mencari data sektor dengan id
            $data = Subjek::find($id);

            // Mengecek apabila terdapat data dengan id yang sama
            if ($data) {
                return $this->successResponseWithData(
                    $data,
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

    public function update(Request $request, $id) {
        try {
            // Membuat Validasi
            $req = $request->validate([
                'nama' => 'required|string|max:255',  // Validasi setiap 'nama' di dalam array sebagai string
                'indikator_id' => 'required|integer|exists:indikator,id',  // Validasi setiap 'indikator_id' di dalam array sebagai integer dan harus ada di tabel 'indikators'
            ]);

            $data = Subjek::find($id);

            if($data) {
                $data->update($req);
                return $this->successResponseWithData(
                    SubjekResource::make($data),
                    'success',
                    Response::HTTP_OK
                );
            }else {
                return $this->errorResponse(
                    'Data tidak ditemukan',
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
        }catch (ValidationException $e) {
            // Handle validation errors
            return $this->errorResponse($e->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }catch( Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id) {
        try {
            // Menghapus data sektor sesuai request $sektor
            Subjek::find($id)->delete();
            return $this->successResponse('Data berhasil dihapus', Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroyPivot($id)
    {
        try {
            // Menghapus data sektor sesuai request $sektor
            PivotIndikatorSubjek::find($id)->delete();
            return $this->successResponse('Data berhasil dihapus', Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
