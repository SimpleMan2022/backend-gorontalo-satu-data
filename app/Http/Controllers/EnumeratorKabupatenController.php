<?php

namespace App\Http\Controllers;

use App\Http\Resources\EnumeratorKabupatenResource;
use App\Http\Traits\ApiResponse;
use Exception;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Enumerator;
use App\Models\EnumeratorKabupaten;
use App\Models\OpdKabupaten;
use Illuminate\Http\Request;

class EnumeratorKabupatenController extends Controller
{
    use ApiResponse;
    public function index()
    {
        try{
            $data = EnumeratorKabupaten::orderBy('created_at', 'desc')->get();
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
                'enumerator_id' => 'required',
                'opd_kabupaten_id'=> 'required',
            ]);

            // Check if enumerator exists
            if (!Enumerator::where('id', $validated['enumerator_id'])->exists()) {
                return $this->errorResponse('Enumerator tidak ditemukan', Response::HTTP_BAD_REQUEST);
            }

            // Check if opd_kabupaten exists
            if (!OpdKabupaten::where('id', $validated['opd_kabupaten_id'])->exists()) {
                return $this->errorResponse('OPD Kabupaten tidak ditemukan', Response::HTTP_BAD_REQUEST);
            }

            EnumeratorKabupaten::create([
                'enumerator_id' => $validated['enumerator_id'],
                'opd_kabupaten_id' => $validated['opd_kabupaten_id'],
            ]);
            
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
            $data = EnumeratorKabupaten::find($id);

            if($data){
                return $this->successResponseWithData(
                    EnumeratorKabupatenResource::make($data),
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
                'opd_kabupaten_id' => 'required',
                'enumerator_id' => 'required',
            ]);

            if (!Enumerator::where('id', $validated['enumerator_id'])->exists()) {
                return $this->errorResponse('Enumerator tidak ditemukan', Response::HTTP_BAD_REQUEST);
            }

            // Check if opd_kabupaten exists
            if (!OpdKabupaten::where('id', $validated['opd_kabupaten_id'])->exists()) {
                return $this->errorResponse('OPD Kabupaten tidak ditemukan', Response::HTTP_BAD_REQUEST);
            }

            $enumerator_kabupaten = EnumeratorKabupaten::find($id);
            $enumerator_kabupaten->update($validated);
            
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
            $enumerator_kabupaten = EnumeratorKabupaten::find($id);
            $enumerator_kabupaten->delete();

            return $this->successResponse('successfully deleted data', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
