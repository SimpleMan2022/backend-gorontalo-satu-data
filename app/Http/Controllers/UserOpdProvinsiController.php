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
    
    public function index()
    {
        try {
            $data = UserOpdProvinsi::orderBy('created_at', 'desc')->get();
            if ($data->isNotEmpty()) {
                return $this->successResponseWithData($data, 'success', Response::HTTP_OK);
            } else {
                return response()->json([
                    'message' => 'Data masih kosong.'
                ]);
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
                'password' => 'required',
                'nama_opd' => 'required',
                'lokasi_opd' => 'required',
                'user_jabatan' => 'required',
            ]);

            // Mengubah menjadi huruf kecil
            $lowercaseString = strtolower($request->nama);
            // Menghapus spasi
            $validated['username'] = str_replace(' ', '', $lowercaseString);

            $validated['password'] = Hash::make($request->password);
            $user = User::create($validated);
            if ($user){
                $opd_provinsi = OpdProvinsi::create([
                    'nama' => $request->nama_opd,
                    'lokasi' => $request->lokasi_opd,
                ]);
                if ($opd_provinsi){
                    $user_opd_provinsi = UserOpdProvinsi::create([
                        'nama' => $request->nama,
                        'opd_provinsi_id' => $opd_provinsi->id,
                        'user_id' => $user->id,
                        'jabatan' => $request->user_jabatan,
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
            // Mencari data sektor dengan id
            $data = UserOpdProvinsi::find($id);

            // Mengecek apabila terdapat data dengan id yang sama
            if ($data) {
                return $this->successResponseWithData(
                    UserOpdProvinsiResource::make($data),
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

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'email' => 'required',
                'nama' => 'required',
                'password' => 'required',
                'nama_opd' => 'required',
                'lokasi_opd' => 'required',
                'user_jabatan' => 'required',
            ]);

            $user_opd_provinsi = UserOpdProvinsi::find($id);

            // Mengubah menjadi huruf kecil
            $lowercaseString = strtolower($request->nama);
            // Menghapus spasi
            $validated['username'] = str_replace(' ', '', $lowercaseString);

            $validated['password'] = Hash::make($request->password);
            $user = User::find($user_opd_provinsi->user_id);
            $user->update($validated);
            if ($user){
                
                $opd_provinsi = OpdProvinsi::find($user_opd_provinsi->opd_provinsi_id);

                $opd_provinsi->update([
                    'nama' => $request->nama_opd,
                    'lokasi' => $request->lokasi_opd,
                ]);
                
                if ($opd_provinsi){
                    // $user_opd_provinsi = UserOpdProvinsi::find($user_opd_provinsi->id)

                    $user_opd_provinsi->update([
                        'nama' => $request->nama,
                        'opd_provinsi_id' => $opd_provinsi->id,
                        'user_id' => $user->id,
                        'jabatan' => $request->user_jabatan,
                    ]);
                }
            }
            return $this->successResponse('Data berhasil diperbarui', Response::HTTP_CREATED);
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
            $user_opd_provinsi = UserOpdProvinsi::find($id);
            $user_opd_provinsi->delete();
            return $this->successResponse('successfully deleted data', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
