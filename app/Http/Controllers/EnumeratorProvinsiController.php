<?php

namespace App\Http\Controllers;

use App\Http\Resources\EnumeratorProvinsiResource;
use App\Http\Traits\ApiResponse;
use Exception;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Enumerator;
use App\Models\EnumeratorProvinsi;
use App\Models\OpdProvinsi;
use Illuminate\Http\Request;

class EnumeratorProvinsiController extends Controller
{
    use ApiResponse;
    public function index(Request $request)
    {
        try{
            $perPage = $request->per_page ?? 10;
            $EnumeratorProvinsiQuery = EnumeratorProvinsi::orderBy('created_at', 'desc');

            if ($opdProvinsiId = $request->query('opd_provinsi_id')) {
                $EnumeratorProvinsiQuery->where('opd_provinsi_id', $opdProvinsiId);
            }

            if ($enumeratorId = $request->query('enumerator_id')) {
                $EnumeratorProvinsiQuery->where('enmuerator_id', $enumeratorId);
            }

            $data = $EnumeratorProvinsiQuery->paginate($perPage);

            if ($data->isNotEmpty()){
                return $this->successResponseWithDataIndex(
                    $data,
                    EnumeratorProvinsiResource::collection($data),
                    'success', 
                    response::HTTP_OK
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
                'enumerator_id' => 'required',
                'opd_provinsi_id'=> 'required',
            ]);

            // Check if enumerator exists
            if (!Enumerator::where('id', $validated['enumerator_id'])->exists()) {
                return $this->errorResponse('Enumerator tidak ditemukan', Response::HTTP_BAD_REQUEST);
            }

            // Check if opd_provinsi exists
            if (!OpdProvinsi::where('id', $validated['opd_provinsi_id'])->exists()) {
                return $this->errorResponse('OPD Provinsi tidak ditemukan', Response::HTTP_BAD_REQUEST);
            }

            EnumeratorProvinsi::create([
                'enumerator_id' => $validated['enumerator_id'],
                'opd_provinsi_id' => $validated['opd_provinsi_id'],
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
            $data = EnumeratorProvinsi::find($id);

            if($data){
                return $this->successResponseWithData(
                    EnumeratorProvinsiResource::make($data),
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
                'opd_provinsi_id' => 'required',
                'enumerator_id' => 'required',
            ]);

            if (!Enumerator::where('id', $validated['enumerator_id'])->exists()) {
                return $this->errorResponse('Enumerator tidak ditemukan', Response::HTTP_BAD_REQUEST);
            }

            if (!OpdProvinsi::where('id', $validated['opd_provinsi_id'])->exists()) {
                return $this->errorResponse('OPD Provinsi tidak ditemukan', Response::HTTP_BAD_REQUEST);
            }

            $enumerator_Provinsi = EnumeratorProvinsi::find($id);
            $enumerator_Provinsi->update($validated);
            
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
            $enumerator_provinsi = EnumeratorProvinsi::find($id);
            $enumerator_provinsi->delete();

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

            $enumerator_provinsi = EnumeratorProvinsi::whereIn('id', $ids)->pluck('id')->toArray();

            $notFoundIds = array_diff($ids, $enumerator_provinsi);

            if (!empty($notFoundIds)) {
                return $this->errorResponse('Data dengan ID berikut tidak ditemukan: ' . implode(', ', $notFoundIds), Response::HTTP_NOT_FOUND);
            }

            EnumeratorProvinsi::whereIn('id', $enumerator_provinsi)->delete();

            return $this->successResponse('Data enumerator provinsi berhasil dihapus', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
