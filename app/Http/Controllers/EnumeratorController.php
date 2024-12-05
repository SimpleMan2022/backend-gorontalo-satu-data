<?php

namespace App\Http\Controllers;

use App\Http\Resources\{EnumeratorResource, EnumeratorMobileResource};
use App\Http\Traits\ApiResponse;
use App\Models\User;
use Exception;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use App\Models\{Enumerator, RekapHasil};
use App\Models\EnumeratorKabupaten;
use App\Models\EnumeratorProvinsi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use ModelNotFoundException;

class EnumeratorController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $perPage = $request->per_page ?? 10;
            $enumeratorQuery = Enumerator::orderBy('created_at', 'desc');

            if ($user->hasRole('opd_provinsi')) {
                $opdProvinsiId = $user->user_opd_provinsi()->first()->opd_provinsi_id;
                $enumeratorQuery->whereHas('enumerator_provinsi', function ($query) use ($opdProvinsiId) {
                    $query->where('opd_provinsi_id', $opdProvinsiId);
                });
            } elseif ($user->hasRole('opd_kabupaten')) {
                $opdKabupatenId = $user->user_opd_kabupaten()->first()->opd_kabupaten_id;
                $enumeratorQuery->whereHas('enumerator_kabupaten', function ($query) use ($opdKabupatenId) {
                    $query->where('opd_kabupaten_id', $opdKabupatenId);
                });
            }

            if ($search = $request->query('search')) {
                $enumeratorQuery->where(function ($query) use ($search) {
                    $query->where('nama', 'like', '%' . $search . '%')
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query->where('email', 'like', '%' . $search . '%')
                                ->orWhere('username', 'like', '%' . $search . '%');
                        });
                });
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


    public function byIndikatorMobile(Request $request)
    {
        try {

            $enumeratorQuery = Enumerator::orderBy('created_at', 'desc');

            if ($userId = $request->query('user_id')) {
                $enumeratorQuery->where('user_id', $userId);
            }

            $data = $enumeratorQuery->first();


            if ($data) {
                return $this->successResponseWithData(
                    EnumeratorMobileResource::make($data),
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
            $user = $request->user();
            $validated = $request->validate([
                'email' => 'required|email|unique:users',
                'nama' => 'required',
                'jabatan' => 'required',
            ]);

            if ($user->hasRole('opd_provinsi')) {
                $validated['opd_provinsi_id'] = $user->user_opd_provinsi()->first()->opd_provinsi_id;
            } elseif ($user->hasRole('opd_kabupaten')) {
                $validated['opd_kabupaten_id'] = $user->user_opd_kabupaten()->first()->opd_kabupaten_id;
            }

            // Mengubah menjadi huruf kecil
            $lowercaseString = strtolower($request->nama);
            // Menghapus spasi
            $validated['username'] = str_replace(' ', '', $lowercaseString);

            $validated['password'] = Hash::make(env('PASSWORD_OPD'));
            $newUser = User::create($validated);
            if ($newUser) {
                // Create Enumerator entry
                $enumerator = Enumerator::create([
                    'nama' => $request->nama,
                    'user_id' => $newUser->id,
                    'jabatan' => $request->jabatan,
                ]);
                $newUser->assignRole('enumerator');
                // Check for opd_kabupaten_id and create in EnumeratorKabupaten
                if ($user->hasRole('opd_kabupaten')) {
                    EnumeratorKabupaten::create([
                        'opd_kabupaten_id' => $user->user_opd_kabupaten()->first()->opd_kabupaten_id,
                        'enumerator_id' => $enumerator->id,
                    ]);
                }

                // Check for opd_provinsi_id and create in EnumeratorProvinsi
                if ($user->hasRole('opd_provinsi')) {
                    EnumeratorProvinsi::create([
                        'opd_provinsi_id' => $user->user_opd_provinsi()->first()->opd_provinsi_id,
                        'enumerator_id' => $enumerator->id,
                    ]);
                }
                // $enumerator->assignRole('enumerator');
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
            $loginUser = $request->user();
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
            ]);

            if ($loginUser->hasRole('opd_provinsi')) {
                $validated['opd_provinsi_id'] = $loginUser->user_opd_provinsi()->first()->opd_provinsi_id;
            } elseif ($loginUser->hasRole('opd_kabupaten')) {
                $validated['opd_kabupaten_id'] = $loginUser->user_opd_kabupaten()->first()->opd_kabupaten_id;
            }

            // Update data user
            $user->update([
                'email' => $validated['email'],
                'username' => $request->nama,
            ]);

            // Update data enumerator
            $enumerator->update([
                'nama' => $request->nama,
                'jabatan' => $request->jabatan,
            ]);

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
            if (!$enumerator) {
                return $this->errorResponse('Data tidak ditemukan', Response::HTTP_NOT_FOUND);
            }
            $enumerator->delete();

            User::where('id', $enumerator->user_id)->delete();

            // Check for rekap hasil with status 'v3' and delete them

            if ($enumerator->enumerator_level == 'provinsi') {
                $rekapHasilV3 = RekapHasil::where('enumerator_id', $id)->where('status', 'v2')->get();
                foreach ($rekapHasilV3 as $rekap) {
                    $rekap->delete();
                }
            } else if ($enumerator->enumerator_level == 'kabupaten') {
                $rekapHasilV3 = RekapHasil::where('enumerator_id', $id)->where('status', 'v3')->get();
                foreach ($rekapHasilV3 as $rekap) {
                    $rekap->delete();
                }
            }
            return $this->successResponse('Data berhasil dihapus', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function multiDestroy()
    {
        try {
            $id = request('id');
            $ids = explode(",", $id);

            $enumeratorId = Enumerator::whereIn('id', $ids)->pluck('id')->toArray();

            $enumerator = Enumerator::whereIn('id', $ids)->get();

            $notFoundIds = array_diff($ids, $enumeratorId);

            if (!empty($notFoundIds)) {
                return $this->errorResponse('Data dengan ID berikut tidak ditemukan: ' . implode(', ', $notFoundIds), Response::HTTP_NOT_FOUND);
            }

            // Hapus data user
            User::whereIn('id', $enumerator->pluck('user_id'))->delete();

            $enumeratorDelete = Enumerator::whereIn('id', $enumeratorId)->delete();
            // dd($enuemrator[0]->enumerator_level);
            if ($enumerator[0]->enumerator_level == 'provinsi') {
                $rekapHasilV3 = RekapHasil::where('enumerator_id', $id)->where('status', 'v2')->get();
                foreach ($rekapHasilV3 as $rekap) {
                    $rekap->delete();
                }
            } else if ($enumerator[0]->enumerator_level == 'kabupaten') {
                $rekapHasilV3 = RekapHasil::where('enumerator_id', $id)->where('status', 'v3')->get();
                foreach ($rekapHasilV3 as $rekap) {
                    $rekap->delete();
                }
            }
            return $this->successResponse('Data enumerator berhasil dihapus', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
