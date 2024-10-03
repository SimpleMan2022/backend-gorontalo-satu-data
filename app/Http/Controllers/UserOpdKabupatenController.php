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

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserOpdKabupatenController extends Controller
{
    use ApiResponse;
    public function index()
    {
        try{
            $data = UserOpdKabupaten::orderBy('created_at', 'desc')->get();
            if ($data->isNotEmpty()){
                return $this->successResponseWithData($data,'success', Response::HTTP_OK);
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
                'password' => 'required',
                'opd_kabupaten_id' => 'required',
                'user_jabatan' => 'required',
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

            $validated['password'] = Hash::make('gsdmakro123');
            $user = User::create($validated);
            if ($user){
                
                    UserOpdKabupaten::create([
                        'nama' => $request->nama,
                        'opd_kabupaten_id' => $validated["opd_kabupaten_id"],
                        'user_id' => $user->id,
                        'jabatan' => $request->user_jabatan,
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
        try{
            $data = UserOpdKabupaten::find($id);

            if($data){
                return $this->successResponseWithData(
                    UserOpdKabupatenResource::make($data),
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

    public function byOpdKabupaten(string $id)
    {
        try {
            // Retrieve all records where the OpdKabupaten ID matches
            $data = UserOpdKabupaten::where('opd_kabupaten_id', $id)->get();

            if ($data->isNotEmpty()) {
                return $this->successResponseWithData(
                    UserOpdKabupatenResource::collection($data),
                    'success',
                    Response::HTTP_OK
                );
            } else {
                return response()->json([
                    'message' => 'Data tidak ditemukan.'
                ], Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
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
                'email' => 'required',
                'nama' => 'required',
                // 'password' => 'required',
                'nama_opd' => 'required',
                'lokasi_opd' => 'required',
                'user_jabatan' => 'required',
            ]);

            $user_opd_kabupaten = UserOpdKabupaten::find($id);

            // Mengubah menjadi huruf kecil
            $lowercaseString = strtolower($request->nama);
            // Menghapus spasi
            $validated['username'] = str_replace(' ', '', $lowercaseString);

            // $validated['password'] = Hash::make($request->password);
            $user = User::find($user_opd_kabupaten->user_id);
            $user->update($validated);
            if ($user){
                
                $opd_kabupaten = OpdKabupaten::find($user_opd_kabupaten->opd_kabupaten_id);

                $opd_kabupaten->update([
                    'nama' => $request->nama_opd,
                    'lokasi' => $request->lokasi_opd,
                ]);
                
                if ($opd_kabupaten){

                    $user_opd_kabupaten->update([
                        'nama' => $request->nama,
                        'opd_kabupaten_id' => $opd_kabupaten->id,
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {

            // Menghapus data sektor sesuai request $sektor
            $user_opd_kabupaten = UserOpdKabupaten::find($id);
            $user_opd_kabupaten->delete();
            return $this->successResponse('successfully deleted data', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
