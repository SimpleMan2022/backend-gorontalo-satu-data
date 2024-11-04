<?php

namespace App\Http\Controllers;

use App\Http\Resources\EnumeratorResource;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use Exception;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Enumerator;
use App\Models\EnumeratorKabupaten;
use App\Models\EnumeratorProvinsi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EnumeratorController extends Controller
{
    use ApiResponse;
    public function index(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 10;
            $enumeratorQuery = Enumerator::orderBy('created_at', 'desc');

            if ($search = $request->query('search')) {
                $enumeratorQuery->where(function ($query) use ($search) {
                    $query->where('nama', 'like', '%' . $search . '%')
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query->where('email', 'like', '%' . $search . '%')
                                ->orWhere('username', 'like', '%' . $search . '%');
                        });
                });
            }

            if ($userId = $request->query('user_id')) {
                $enumeratorQuery->where('user_id', $userId);
            }

            $data = $enumeratorQuery->paginate($perPage);

            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    EnumeratorResource::collection($data),
                    'Data enumerator berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse('Data enumerator masih kosong', Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email|unique:users',
                'nama' => 'required',
                'jabatan' => 'required',
                'opd_kabupaten_id' => 'nullable|integer|prohibited_if:opd_provinsi_id,!=null',
                'opd_provinsi_id' => 'nullable|integer|prohibited_if:opd_kabupaten_id,!=null',
            ]);

            // Mengubah menjadi huruf kecil
            $lowercaseString = strtolower($request->nama);
            // Menghapus spasi
            $validated['username'] = str_replace(' ', '', $lowercaseString);

            $validated['password'] = Hash::make(env('PASSWORD_OPD'));
            $user = User::create($validated);
            if ($user) {
                // Create Enumerator entry
                $enumerator = Enumerator::create([
                    'nama' => $request->nama,
                    'user_id' => $user->id,
                    'jabatan' => $request->jabatan,
                ]);

                $user->assignRole('enumerator');
                // Check for opd_kabupaten_id and create in EnumeratorKabupaten
                if ($request->opd_kabupaten_id) {
                    EnumeratorKabupaten::create([
                        'opd_kabupaten_id' => $request->opd_kabupaten_id,
                        'enumerator_id' => $enumerator->id,
                    ]);
                }

                // Check for opd_provinsi_id and create in EnumeratorProvinsi
                if ($request->opd_provinsi_id) {
                    EnumeratorProvinsi::create([
                        'opd_provinsi_id' => $request->opd_provinsi_id,
                        'enumerator_id' => $enumerator->id,
                    ]);
                }
                $enumerator->assignRole('enumerator');
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
            $data = Enumerator::find($id);

            if ($data) {
                return $this->successResponseWithData(
                    EnumeratorResource::make($data),
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

    public function update(Request $request, string $id)
    {
        try {
            // Temukan enumerator terlebih dahulu
            $enumerator = Enumerator::find($id);

            if (!$enumerator) {
                return $this->errorResponse('Data enumerator tidak ditemukan', Response::HTTP_NOT_FOUND);
            }

            // Temukan user yang terkait dengan enumerator
            $user = User::find($enumerator->user_id);
            if (!$user) {
                return $this->errorResponse('Data user tidak ditemukan', Response::HTTP_NOT_FOUND);
            }

            // Lakukan validasi
            $validated = $request->validate([
                'email' => 'required|email|unique:users,email,' . $user->id . ',id',
                'nama' => 'required',
                'jabatan' => 'required',
                'opd_kabupaten_id' => 'nullable|integer|prohibited_if:opd_provinsi_id,!=null',
                'opd_provinsi_id' => 'nullable|integer|prohibited_if:opd_kabupaten_id,!=null',
            ]);

            // Update data user
            $user->update([
                'email' => $validated['email'],
                'nama' => $request->nama,
            ]);

            // Update data enumerator
            $enumerator->update([
                'nama' => $request->nama,
                'jabatan' => $request->jabatan,
            ]);

            // Cek jika perpindahan dari opd_kabupaten ke opd_provinsi
            if ($request->opd_provinsi_id) {
                // Hapus data di EnumeratorKabupaten jika ada perpindahan ke opd_provinsi
                EnumeratorKabupaten::where('enumerator_id', $id)->delete();

                // Cek apakah EnumeratorProvinsi sudah ada
                $enumeratorProvinsi = EnumeratorProvinsi::where('enumerator_id', $id)->first();
                if ($enumeratorProvinsi) {
                    $enumeratorProvinsi->update([
                        'opd_provinsi_id' => $request->opd_provinsi_id,
                    ]);
                } else {
                    EnumeratorProvinsi::create([
                        'opd_provinsi_id' => $request->opd_provinsi_id,
                        'enumerator_id' => $id,
                    ]);
                }
            } elseif ($request->opd_kabupaten_id) {
                // Hapus data di EnumeratorProvinsi jika ada perpindahan ke opd_kabupaten
                EnumeratorProvinsi::where('enumerator_id', $id)->delete();

                // Cek apakah EnumeratorKabupaten sudah ada
                $enumeratorKabupaten = EnumeratorKabupaten::where('enumerator_id', $id)->first();
                if ($enumeratorKabupaten) {
                    $enumeratorKabupaten->update([
                        'opd_kabupaten_id' => $request->opd_kabupaten_id,
                    ]);
                } else {
                    EnumeratorKabupaten::create([
                        'opd_kabupaten_id' => $request->opd_kabupaten_id,
                        'enumerator_id' => $id,
                    ]);
                }
            }

            return $this->successResponse('Data enumerator berhasil diubah', Response::HTTP_OK);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal diubah', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function destroy(string $id)
    {
        try {
            // Menghapus data sektor sesuai request $sektor
            $enumerator = Enumerator::find($id);
            $enumerator->delete();

            return $this->successResponse('Data berhasil diambil', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function multiDestroy()
    {
        try {
            $id = request('id');
            $ids = explode(",", $id);

            $enumerator = Enumerator::whereIn('id', $ids)->pluck('id')->toArray();
            $notFoundIds = array_diff($ids, $enumerator);

            if (!empty($notFoundIds)) {
                return $this->errorResponse('Data dengan ID berikut tidak ditemukan: ' . implode(', ', $notFoundIds), Response::HTTP_NOT_FOUND);
            }

            Enumerator::whereIn('id', $enumerator)->delete();
            return $this->successResponse('Data enumerator berhasil dihapus', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
