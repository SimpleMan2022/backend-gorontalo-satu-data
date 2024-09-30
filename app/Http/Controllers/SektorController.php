<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sektor;
use App\Http\Resources\SektorResource;
use App\Http\Traits\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

class SektorController extends Controller
{
    // Memanggil Traits Api Response
    use ApiResponse;

    public function index() {
        try {
            // Memanggil data dan request PerPage
            $per_page = $request->perPage ?? 10;
            $data = Sektor::orderBy('created_at','desc')->paginate($per_page);

            // Mengecek apabila terdapat data
            if($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    SektorResource::collection($data),
                    'success',
                    Response::HTTP_OK
                );
                // return response()->json([
                //     'data' => $data
                // ]);
            }else {
                return response()->json([
                    'message' => 'Data masih kosong.'
                ]);
            }
        }catch(Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request) {
        try {
            // Membuat validasi
            $req = $request->validate([
                'nama' => 'required'
            ]);

            $sektor = Sektor::create($req);
            return $this->successResponseWithData($sektor, 'Data berhasil dibuat',Response::HTTP_CREATED);
        }catch( Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id) {
        try {
            // Mencari data sektor dengan id
            $data = Sektor::find($id);

            // Mengecek apabila terdapat data dengan id yang sama
            if($data) {
                return $this->successResponseWithData(
                    SektorResource::make($data),
                    'success',
                    Response::HTTP_OK
                );
            }else {
                return response()->json([
                    'message' => 'Data tidak ditemukan.'
                ]);
            }
        }catch(Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, Sektor $sektor) {
        try {
            // Membuat Validasi
            $req = $request->validate([
                'nama' => 'required'
            ]);

            // Memperbarui data
            $sektor->update($req);
            return $this->successResponseWithData($sektor, 'Data berhasil diperbarui',Response::HTTP_CREATED);
            // return $request;
        }catch( Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Sektor $sektor) {
        try {

            // Menghapus data sektor sesuai request $sektor
            $sektor->delete();
            return $this->successResponse('successfully deleted data', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
