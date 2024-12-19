<?php

namespace App\Http\Controllers;

use App\Models\UserOpdProvinsi;
use App\Http\Traits\ApiResponse;
use App\Models\OpdProvinsi;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Resources\UserOpdProvinsiResource;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserOpdProvinsiController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 10;
            $UserOpdProvinsiQuery = UserOpdProvinsi::orderBy('created_at', 'desc');

            $search = $request->query('search');
            if ($search) {
                $UserOpdProvinsiQuery->where('nama', 'like', '%' . $search . '%');
            }
            if ($request->has('opd_provinsi_id')) {
                $UserOpdProvinsiQuery->where('opd_provinsi_id', $request->opd_provinsi_id);
            }

            if ($userId = $request->query('user_id')) {
                $UserOpdProvinsiQuery->where('user_id', $userId);
            }

            if ($opdProvinsiId = $request->query('opd_provinsi_id')) {
                $UserOpdProvinsiQuery->where('opd_provinsi_id', $opdProvinsiId);
            }

            $data = $UserOpdProvinsiQuery->paginate($perPage);

            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    UserOpdProvinsiResource::collection($data),
                    'Data user OPD Provinsi berhasil diambil',
                    Response::HTTP_OK
                );
                // return $data;
            } else {
                return $this->errorResponse('Data masih kosong', Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getUser(Request $request)
    {
        try {
            $data = UserOpdProvinsi::where('user_id', $request->user_id)->first();

            if ($data) {
                return $this->successResponseWithData(
                    UserOpdProvinsiResource::make($data),
                    'Data user OPD Provinsi berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse('Data user OPD Provinsi tidak ditemukan', Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            $userLogin = $request->user();
            $validated = $request->validate([
                'email' => 'required',
                'nama' => 'required',
                'jabatan' => 'required',
                'opd_provinsi_id' => $userLogin->hasRole('admin') ? 'required' : '',
            ]);

            if ($userLogin->hasRole('opd_provinsi')) {
                $validated['opd_provinsi_id'] = $userLogin->user_opd_provinsi->opd_provinsi_id;
            }

            if (User::where('email', $validated['email'])->exists()) {
                return $this->errorResponse('Email telah terdaftar', Response::HTTP_BAD_REQUEST);
            }

            if (!OpdProvinsi::where('id', $validated['opd_provinsi_id'])->exists()) {
                return $this->errorResponse('OPD tidak ditemukan', Response::HTTP_BAD_REQUEST);
            }

            // Mengubah menjadi huruf kecil
            $lowercaseString = strtolower($request->nama);
            // Menghapus spasi
            $validated['username'] = str_replace(' ', '', $lowercaseString);
            $baseUsername = $validated['username'];
            $counter = 1;
            while (User::where('username', $validated['username'])->exists()) {
                $validated['username'] = $baseUsername . $counter;
                $counter++;
            }
            $validated['password'] = Hash::make(env('PASSWORD_OPD'));
            // dd($validated);
            $user = User::create([
                'email' => $validated['email'],
                'username' => $validated['username'],
                'password' => $validated['password'],
            ]);
            if ($request->jabatan == 'Admin') {
                $permission_operator_provinsi = [
                    'kelola_user_opd_provinsi',
                    'kelola_opd_provinsi',
                ];

                $user->givePermissionTo($permission_operator_provinsi);
            }
            $user->assignRole('opd_provinsi');
            if ($user) {
                UserOpdProvinsi::create([
                    'nama' => $request->nama,
                    'opd_provinsi_id' => $validated["opd_provinsi_id"],
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

    public function show(string $id)
    {
        try {
            // Mencari data user opd provinsi dengan id
            $data = UserOpdProvinsi::findOrFail($id);

            return $this->successResponseWithData(
                UserOpdProvinsiResource::make($data),
                'Data user OPD Provinsi berhasil diambil',
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
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
                'jabatan' => 'required',
            ]);

            $user_opd_provinsi = UserOpdProvinsi::find($id);

            // Mengubah menjadi huruf kecil
            $lowercaseString = strtolower($request->nama);
            // Menghapus spasi
            $validated['username'] = str_replace(' ', '', $lowercaseString);
            $baseUsername = $validated['username'];
            $counter = 1;
            while (User::where('username', $validated['username'])->exists()) {
                $validated['username'] = $baseUsername . $counter;
                $counter++;
            }
            // $validated['password'] = Hash::make($request->password);
            $user = User::find($user_opd_provinsi->user_id);
            $user->update([
                'email' => $request->email,
                'username' => $validated['username'],
            ]);

            if ($request->jabatan != 'Admin') {
                $permission_operator_provinsi = [
                    'kelola_user_opd_provinsi',
                    'kelola_opd_provinsi',
                ];

                $user->revokePermissionTo($permission_operator_provinsi);
            } else {
                $permission_operator_provinsi = [
                    'kelola_user_opd_provinsi',
                    'kelola_opd_provinsi',
                ];

                $user->givePermissionTo($permission_operator_provinsi);
            }

            if ($user) {
                $user_opd_provinsi->update([
                    'nama' => $request->nama,
                    'jabatan' => $request->jabatan,
                ]);
            }
            return $this->successResponse('Data User OPD Provinsi berhasil diubah', Response::HTTP_CREATED);
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
            $user_opd_provinsi = UserOpdProvinsi::findOrFail($id);
            $user = User::find($user_opd_provinsi->user_id);
            $user_opd_provinsi->delete();
            $user->delete();
            return $this->successResponse('Data berhasil dihapus', Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Data tidak ditemukan', Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function multiDestroy()
    {
        try {
            $id = request('id');
            $ids = explode(",", $id);

            $UserOpdProvinsi = UserOpdProvinsi::whereIn('id', $ids)->pluck('id')->toArray();

            $notFoundIds = array_diff($ids, $UserOpdProvinsi);

            if (!empty($notFoundIds)) {
                return $this->errorResponse('Data dengan ID berikut tidak ditemukan: ' . implode(', ', $notFoundIds), Response::HTTP_NOT_FOUND);
            }

            UserOpdProvinsi::whereIn('id', $UserOpdProvinsi)->delete();

            return $this->successResponse('Data Opd Provinsi berhasil dihapus', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
