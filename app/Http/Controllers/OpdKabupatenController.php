<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ConvertImage;
use App\Http\Traits\ApiResponse;
use App\Models\{OpdKabupaten, UserOpdKabupaten};
use Exception;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Resources\OpdKabupatenResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OpdKabupatenController extends Controller
{
    use ApiResponse;
    public function index(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 10;

            $opdQuery = OpdKabupaten::orderBy('created_at', 'desc');
            if ($search = $request->query('search')) {
                $opdQuery->where('nama', 'like', '%' . $search . '%');
            }
            if ($kab_id = $request->query('kabupaten_id')) {
                $opdQuery->where('kabupaten_id', $kab_id);
            }

            if ($opdProvId = $request->query('opd_provinsi_id')) {
                $opdQuery->where('opd_provinsi_id', $opdProvId);
            }

            $data = $opdQuery->paginate($perPage);
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    OpdKabupatenResource::collection($data),
                    'Data OPD Kabupaten berhasil diambil',
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
                'lokasi' => 'required',
                'kabupaten_id' => 'required',
                'opd_provinsi_id' => 'required'
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
            // Mengubah menjadi huruf kecil
            OpdKabupaten::create($validated);
            return $this->successResponse('Data OPD Kabupaten berhasil dibuat', Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal dibuat', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function setPermission(Request $request) {
        try {
            if($request->status == 'aktif') {
                $operator = UserOpdKabupaten::where('opd_kabupaten_id', $request->id)->get();
    
                $permissions_kabupaten = [
                    'kelola_user_opd_kabupaten',
                    'kelola_opd_kabupaten',
                ];
                foreach($operator as $item) {
                    $user = $item->user;
                    $user->givePermissionTo($permissions_kabupaten);                
                }
                return $this->successResponse('OPD Kabupaten berhasil diizinkan', Response::HTTP_OK);
            }else {
                $operator = UserOpdKabupaten::where('opd_kabupaten_id', $request->id)->get();
    
                $permissions_kabupaten = [
                    'kelola_user_opd_kabupaten',
                    'kelola_opd_kabupaten',
                ];
                foreach($operator as $item) {
                    $user = $item->user;
                    $user->revokePermissionTo($permissions_kabupaten);                
                }
                return $this->successResponse('Perizinan OPD Kabupaten berhasil diubah', Response::HTTP_OK);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(string $id)
    {
        try {
            // Mencari data sektor dengan id
            $data = OpdKabupaten::find($id);

            // Mengecek apabila terdapat data dengan id yang sama
            if ($data) {
                return $this->successResponseWithData(
                    OpdkabupatenResource::make($data),
                    'Data OPD Kabupaten berhasil diambil',
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

    public function update(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'nama' => 'required',
                'lokasi' => 'required',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
                'kabupaten_id' => 'required',
                'opd_provinsi_id' => 'required'
            ]);

            $opd_kabupaten = OpdKabupaten::findOrFail($id);

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
                if ($opd_kabupaten->logo && $opd_kabupaten->logo !== $validated['logo']) {
                    Storage::disk('public')->delete($opd_kabupaten->logo);
                }
            }

            $opd_kabupaten->update($validated);

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
            $opd_kabupaten = OpdKabupaten::findOrFail($id);
            if ($opd_kabupaten->logo) {
                Storage::disk('public')->delete($opd_kabupaten->logo);
            }

            $opd_kabupaten->delete();
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

            $opd_kabupaten = OpdKabupaten::whereIn('id', $ids)->pluck('id')->toArray();

            $notFoundIds = array_diff($ids, $opd_kabupaten);

            if (!empty($notFoundIds)) {
                return $this->errorResponse('Data dengan ID berikut tidak ditemukan: ' . implode(', ', $notFoundIds), Response::HTTP_NOT_FOUND);
            }

            foreach ($opd_kabupaten as $id) {
                $opd = OpdKabupaten::find($id);
                if ($opd && $opd->logo) {
                    Storage::disk('public')->delete($opd->logo);
                }
            }

            OpdKabupaten::whereIn('id', $opd_kabupaten)->delete();

            return $this->successResponse('Data OPD Kabupaten berhasil dihapus', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
