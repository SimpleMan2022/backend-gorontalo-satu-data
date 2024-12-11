<?php

namespace App\Http\Controllers;

use App\Http\Resources\BeritaAcaraResource;
use App\Http\Traits\ApiResponse;
use Exception;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use App\Models\BeritaAcara;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BeritaAcaraController extends Controller
{
    use ApiResponse;
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $perPage = $request->per_page ?? 10;
            $BeritaAcaraQuery = BeritaAcara::orderBy('created_at', 'desc');
            if ($user->hasRole('opd_provinsi')) {
                $opdProvinsiId = $user->user_opd_provinsi()->first()->opd_provinsi_id;
                $BeritaAcaraQuery->whereHas('opd_kabupaten', function ($query) use ($opdProvinsiId) {
                    $query->where('opd_provinsi_id', $opdProvinsiId);
                });
            } elseif ($user->hasRole('opd_kabupaten')) {
                $opdProvinsiId = $user->user_opd_kabupaten()->first()->opd_kabupaten()->first()->opd_provinsi_id;
                $BeritaAcaraQuery->where('opd_kabupaten_id', $user->user_opd_kabupaten()->first()->opd_kabupaten_id);
            }

            if ($search = $request->query('search')) {
                $BeritaAcaraQuery->where('nama', 'like', '%' . $search . '%');
            }
            if ($opdKabupatenId = $request->query('opd_kabupaten_id')) {
                $BeritaAcaraQuery->wehere('opd_kabupaten_id', $opdKabupatenId);
            }

            if ($indikatorId = $request->query('indikator')) {
                $BeritaAcaraQuery->where('indikator', $indikatorId);
            }

            $data = $BeritaAcaraQuery->paginate(($perPage));
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    BeritaAcaraResource::collection($data),
                    'Data berita acara berhasil diambil',
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
            // Validate request
            $validated = $request->validate([
                'nama' => 'required|string',
                'file' => 'required|file|mimes:pdf|max:20480', // Restrict file to only PDF
                'indikator_id' => 'required|integer|exists:indikator,id',
            ]);
            $user = $request->user();

            if (!$user->hasRole('opd_kabupaten')) {
                return $this->errorResponse('Anda tidak memiliki akses untuk membuat berita acara', Response::HTTP_FORBIDDEN);
            }
            $validated['status'] = 'private';
            $validated['opd_kabupaten_id'] = $user->user_opd_kabupaten()->first()->opd_kabupaten_id;
            // Handle file upload (PDF only)
            if ($request->hasFile('file')) {
                $timestamp = now()->format('Ymd_His');
                $fileName = "berita_acara_{$timestamp}.pdf";
                $filePath = $request->file('file')->storeAs('berita_acara', $fileName);
                $validated['file'] = $filePath;
            }

            // Convert 'nama' to lowercase before saving
            $validated['nama'] = strtolower($validated['nama']);

            // Create BeritaAcara record
            BeritaAcara::create($validated);

            return $this->successResponse('Data berita acara berhasil dibuat', Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal dibuat', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(string $id)
    {
        try {
            $data = BeritaAcara::find($id);
            if ($data) {
                return $this->successResponseWithData(
                    BeritaAcaraResource::make($data),
                    'Data berita acara berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'nama' => 'required',
                'file' => 'nullable|file|mimes:pdf|max:20480',
                'indikator_id' => 'required|integer|exists:indikator,id',
            ]);

            $user = $request->user();
            $validated['status'] = 'private';

            if (!$user->hasRole('opd_kabupaten')) {
                return $this->errorResponse('Anda tidak memiliki akses untuk membuat berita acara', Response::HTTP_FORBIDDEN);
            }
            $validated['opd_kabupaten_id'] = $user->user_opd_kabupaten()->first()->opd_kabupaten_id;

            $berita_acara = BeritaAcara::find($id);

            if ($request->hasFile('file')) {
                if ($berita_acara->file) {
                    Storage::delete($berita_acara->file);
                }

                $timestamp = now()->format('Ymd_His');
                $fileName = "berita_acara_{$timestamp}.pdf";
                $filePath = $request->file('file')->storeAs('berita_acara', $fileName);
                $validated['file'] = $filePath;
            }

            $berita_acara->update($validated);

            return $this->successResponse('Data berhasil diubah', Response::HTTP_OK);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal diubah', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(string $id)
    {
        try {
            $user = request()->user();
            if (!$user->hasRole('opd_kabupaten')) {
                return $this->errorResponse('Anda tidak memiliki akses untuk membuat berita acara', Response::HTTP_FORBIDDEN);
            }
            // Menghapus data sektor sesuai request $sektor
            $berita_acara = BeritaAcara::findOrFail($id);
            if ($berita_acara->file) {
                Storage::delete($berita_acara->file);
            }
            $berita_acara->delete();
            return $this->successResponse('Data berita acara berhasil dihapus', Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function multiDestroy()
    {
        try {
            $user = request()->user();
            if (!$user->hasRole('opd_kabupaten')) {
                return $this->errorResponse('Anda tidak memiliki akses untuk membuat berita acara', Response::HTTP_FORBIDDEN);
            }
            $id = request('id');
            $ids = explode(",", $id);

            $berita_acara = BeritaAcara::whereIn('id', $ids)->pluck('id')->toArray();

            $notFoundIds = array_diff($ids, $berita_acara);

            if (!empty($notFoundIds)) {
                return $this->errorResponse('Data dengan ID berikut tidak ditemukan: ' . implode(', ', $notFoundIds), Response::HTTP_NOT_FOUND);
            }

            foreach ($berita_acara as $id) {
                $b_acara = BeritaAcara::find($id);
                if ($b_acara && $b_acara->file) {
                    Storage::delete($b_acara->file);
                }
            }

            BeritaAcara::whereIn('id', $berita_acara)->delete();

            return $this->successResponse('Data berita acara berhasil dihapus', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
