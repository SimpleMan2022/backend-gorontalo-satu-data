<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ConvertImage;
use App\Http\Traits\ApiResponse;
use App\Models\OpdProvinsi;
use Exception;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Resources\OpdProvinsiResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OpdProvinsiController extends Controller
{
    use ApiResponse;
    public function index(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 10;
            $data = OpdProvinsi::orderBy('created_at', 'desc')->paginate($perPage);
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    OpdProvinsiResource::collection($data),
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



    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nama' => 'required',
                'logo' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
                'lokasi' => 'required'
            ]);

            $logoPath = $request->file('logo')->store('logo', 'public');
            $extension = $request->file('logo')->getClientOriginalExtension();

            if (strtolower($extension) !== 'webp') {
                $webpPath = storage_path('app/public/logo/' . pathinfo($logoPath, PATHINFO_FILENAME) . '.webp');

                ConvertImage::convertImageToWebP(storage_path('app/public/' . $logoPath), $webpPath);

                // Simpan path WebP ke database
                $validated['logo'] = 'logo/' . basename($webpPath);

                Storage::disk('public')->delete($logoPath);
            } else {
                $validated['logo'] = $logoPath;
            }


            OpdProvinsi::create($validated);
            return $this->successResponse('Data OPD Provinsi berhasil dibuat', Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal dibuat', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(string $id)
    {
        try {
            // Mencari data sektor dengan id
            $data = OpdProvinsi::find($id);

            if ($data) {
                return $this->successResponseWithData(
                    OpdProvinsiResource::make($data),
                    'Data OPD Provinsi berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'nama' => 'required',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
                'lokasi' => 'required',
            ]);

            $opd_provinsi = OpdProvinsi::findOrFail($id);

            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('logo', 'public');
                $extension = $request->file('logo')->getClientOriginalExtension();

                if (strtolower($extension) !== 'webp') {
                    $webpPath = storage_path('app/public/logo/' . pathinfo($logoPath, PATHINFO_FILENAME) . '.webp');

                    ConvertImage::convertImageToWebP(storage_path('app/public/' . $logoPath), $webpPath);

                    $validated['logo'] = 'logo/' . basename($webpPath);

                    Storage::disk('public')->delete($logoPath);
                } else {
                    $validated['logo'] = $logoPath;
                }

                // Hapus logo lama jika ada
                if ($opd_provinsi->logo && $opd_provinsi->logo !== $validated['logo']) {
                    Storage::disk('public')->delete($opd_provinsi->logo);
                }
            }

            $opd_provinsi->update($validated);

            return $this->successResponse('Data berhasil diubah', Response::HTTP_CREATED);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal dibuat', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(string $id)
    {
        try {
            // Menghapus data sektor sesuai request $sektor
            $opd_provinsi = OpdProvinsi::findOrFail($id);

            if ($opd_provinsi->logo) {
                Storage::disk('public')->delete($opd_provinsi->logo);
            }

            $opd_provinsi->delete();
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

            $opd_provinsi = OpdProvinsi::whereIn('id', $ids)->pluck('id')->toArray();

            $notFoundIds = array_diff($ids, $opd_provinsi);

            if (!empty($notFoundIds)) {
                return $this->errorResponse('Data dengan ID berikut tidak ditemukan: ' . implode(', ', $notFoundIds), Response::HTTP_NOT_FOUND);
            }

            foreach ($opd_provinsi as $id) {
                $opd = OpdProvinsi::find($id);
                if ($opd && $opd->logo) {
                    Storage::disk('public')->delete($opd->logo);
                }
            }

            OpdProvinsi::whereIn('id', $opd_provinsi)->delete();

            return $this->successResponse('Data OPD Provinsi berhasil dihapus', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
