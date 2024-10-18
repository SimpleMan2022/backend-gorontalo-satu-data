<?php

namespace App\Http\Controllers;

use App\Models\UserOpdKabupaten;
use App\Http\Traits\ApiResponse;
use App\Models\OpdKabupaten;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Resources\UserOpdKabupatenResource;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserOpdKabupatenController extends Controller
{
    use ApiResponse;
    public function index(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 10;
            $data = UserOpdKabupaten::orderBy('created_at', 'desc')->paginate($perPage);
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    UserOpdKabupatenResource::collection($data),
                    'Data user OPD Kabupaten berhasil diambil',
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
                'email' => 'required',
                'nama' => 'required',
                'opd_kabupaten_id' => 'required',
                'jabatan' => 'required',
            ]);

            if (User::where('email', $validated['email'])->exists()) {
                return $this->errorResponse('Email telah terdaftar', Response::HTTP_BAD_REQUEST);
            }

            if (!OpdKabupaten::where('id', $validated['opd_kabupaten_id'])->exists()) {
                return $this->errorResponse('OPD tidak ditemukan', Response::HTTP_BAD_REQUEST);
            }

            // Mengubah menjadi huruf kecil
            $lowercaseString = strtolower($request->nama);
            // Menghapus spasi
            $validated['username'] = str_replace(' ', '', $lowercaseString);
            if (User::where('username', $validated['username'])->exists()) {
                return $this->errorResponse('Username telah terdaftar', Response::HTTP_BAD_REQUEST);
            }
            $validated['password'] = Hash::make(env('PASSWORD_OPD'));
            $user = User::create($validated);
            $user->assignRole('opd_kabupaten');
            if ($user) {

                UserOpdKabupaten::create([
                    'nama' => $request->nama,
                    'opd_kabupaten_id' => $validated["opd_kabupaten_id"],
                    'user_id' => $user->id,
                    'jabatan' => $request->jabatan,
                ]);
            }
            return $this->successResponse('Data berhasil dibuat', Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal dibuat', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $data = UserOpdKabupaten::find($id);

            if ($data) {
                return $this->successResponseWithData(
                    UserOpdKabupatenResource::make($data),
                    'Data user OPD Kabupaten berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse('Data user OPD Kabupaten tidak ditemukan', Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'email' => 'required',
                'nama' => 'required',
                'opd_kabupaten_id' => 'required',
                'jabatan' => 'required',
            ]);

            $user_opd_kabupaten = UserOpdKabupaten::find($id);
            if (!$user_opd_kabupaten) {
                return $this->errorResponse('Data user OPD Kabupaten tidak ditemukan', Response::HTTP_NOT_FOUND);
            }
            $user_opd_kabupaten->update([
                'nama' => $request->nama,
                'opd_kabupaten_id' => $validated["opd_kabupaten_id"],
                'jabatan' => $request->jabatan,
            ]);

            $user = User::find($user_opd_kabupaten->user_id);
            if (!$user) {
                return $this->errorResponse('Data user tidak ditemukan', Response::HTTP_NOT_FOUND);
            }
            $lowercaseString = strtolower($request->nama);
            // Menghapus spasi
            $validated['username'] = str_replace(' ', '', $lowercaseString);
            $user->update([
                'email' => $request->email,
                'username' => $validated['username'],
            ]);
            return $this->successResponse('Data user OPD Kabupaten berhasil diubah', Response::HTTP_CREATED);
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
            $user_opd_kabupaten = UserOpdKabupaten::findOrFail($id);
            $user = User::find($user_opd_kabupaten->user_id);
            $user_opd_kabupaten->delete();
            $user->delete();
            return $this->successResponse('Data user OPD Kabupaten berhasil dihapus', Response::HTTP_OK);
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

            $user_opd_kabupaten = UserOpdKabupaten::whereIn('id', $ids)->pluck('id')->toArray();
            $user = UserOpdKabupaten::whereIn('id', $ids)->pluck('user_id')->toArray();
            $notFoundIds = array_diff($ids, $user_opd_kabupaten);

            if (!empty($notFoundIds)) {
                return $this->errorResponse('Data dengan ID berikut tidak ditemukan: ' . implode(', ', $notFoundIds), Response::HTTP_NOT_FOUND);
            }

            UserOpdKabupaten::whereIn('id', $user_opd_kabupaten)->delete();
            User::whereIn('id', $user)->delete();


            return $this->successResponse('Data user Opd Kabupaten berhasil dihapus', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
