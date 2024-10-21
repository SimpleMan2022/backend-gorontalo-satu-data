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
use Spatie\Permission\Models\Role;

class EnumeratorController extends Controller
{
    use ApiResponse;
    public function index()
    {
        try{
            $perPage = $request->per_page ?? 10;
            $data = Enumerator::orderBy('created_at', 'desc')->paginate($perPage);
            if ($data->isNotEmpty()){
                return $this->successResponseWithDataIndex(
                    $data,
                    EnumeratorResource::collection($data),
                    'success', 
                    Response::HTTP_OK
                );
            }else {
                return response()->json([
                    'message' => 'Data masih kosong.'
                ]);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required',
                'nama' => 'required',
                'user_jabatan' => 'required',
                'opd_kabupaten_id'=> 'nullable|integer|prohibited_if:opd_provinsi_id,!=null',
                'opd_provinsi_id'=> 'nullable|integer|prohibited_if:opd_kabupaten_id,!=null',
            ]);

            // Mengubah menjadi huruf kecil
            $lowercaseString = strtolower($request->nama);
            // Menghapus spasi
            $validated['username'] = str_replace(' ', '', $lowercaseString);

            $validated['password'] = Hash::make('gsdmakro123');
            $user = User::create($validated);
            if ($user) {
                // Create Enumerator entry
                $enumerator = Enumerator::create([
                    'nama' => $request->nama,
                    'user_id' => $user->id,
                    'jabatan' => $request->user_jabatan,
                ]);

                
    
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
        try{
            $data = Enumerator::find($id);

            if($data){
                return $this->successResponseWithData(
                    EnumeratorResource::make($data),
                    'success',
                    Response::HTTP_OK
                );
            } else{
                return response()->json([
                    'message' => 'Data tidak ditemukan.'
                ]);
            }
        } catch(Exception $e){
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'nama' => 'required',
                'jabatan' => 'required',
            ]);

            $enumerator = Enumerator::find($id);
            $enumerator->update($validated);

            // Mengubah menjadi huruf kecil
            $lowercaseString = strtolower($request->nama);
            // Menghapus spasi
            $validated['username'] = str_replace(' ', '', $lowercaseString);

            // $validated['password'] = Hash::make($request->password);
            $user = User::find($enumerator->user_id);
            $user->update($validated);
            
            return $this->successResponse('Data berhasil diperbarui', Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal dibuat', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {

            // Menghapus data sektor sesuai request $sektor
            $enumerator = Enumerator::find($id);
            $enumerator->delete();

            $user = User::find($enumerator->user_id);
            $user->delete();
            return $this->successResponse('successfully deleted data', Response::HTTP_OK);
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
