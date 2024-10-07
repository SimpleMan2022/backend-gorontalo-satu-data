<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Variabel, Indikator};
use App\Http\Resources\VariabelResource;
use App\Http\Traits\ApiResponse;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;

class VariabelController extends Controller
{
    use ApiResponse;

    public function index() {
        try{
            $perpage = $request->per_page ?? 10;
            $data = Variabel::orderBy('created_at', 'desc')->paginate($perpage);
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    VariabelResource::collection($data), 
                    'success', 
                    Response::HTTP_OK);
            } else {
                return response()->json([
                    'message' => 'Data masih kosong.'
                ]);
            }
        }catch(Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function byIndikator($indikator) {
        try{
            $perpage = $request->per_page ?? 10;
            $data = Variabel::where('indikator_id', $indikator)->orderBy('order', 'asc')->paginate($perpage);
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    VariabelResource::collection($data), 
                    'success', 
                    Response::HTTP_OK);
            } else {
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
            $req = $request->validate([
                'indikator_id' => 'required',
                'nama' => 'required',
                'tipe' => 'required',
            ]);

            $req['order'] = Variabel::where('indikator_id', $request->indikator_id)->count();

            $variabel = Variabel::create($req);
            return $this->successResponse('Data berhasil dibuat',Response::HTTP_CREATED);

        }catch(Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id) {
        try {
            // Mencari data sektor dengan id
            $data = Variabel::find($id);

            // Mengecek apabila terdapat data dengan id yang sama
            if ($data) {
                return $this->successResponseWithData(
                    $data,
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

    public function update(Request $request, $id) {
        try {
            $req = $request->validate([
                'indikator_id' => 'required',
                'nama' => 'required',
                'tipe' => 'required',
                'order'=> 'required'
            ]);

            // $req['order'] = Variabel::where('indikator_id', $request->indikator_id)->count();

            $data = Variabel::find($id);

            if($data) {
                $data->update($req);
                
                return $this->successResponse('Data berhasil diperbarui',Response::HTTP_CREATED);
            }else {
                return $this->errorResponse(
                    'Data tidak ditemukan',
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        }catch(Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function tukarPosisi(Request $request, $id)
    {
        try {
            $request->validate([
                'indikator_id' => 'required',
                'nama' => 'required',
                'tipe' => 'required',
                'order'=> 'required'
            ]);

            $variabel = Variabel::find($id);
            // dd($variabel);
    
            
            if($variabel) {
                Variabel::where('order', $request->order)
                    ->where('indikator_id', $variabel->indikator_id)
                    ->first()
                    ->update(['order' => $variabel->order]);

                $variabel->update(['order' => $request->order]);

                return $this->successResponse('Data berhasil diperbarui',Response::HTTP_CREATED);
            }else {
                return $this->errorResponse(
                    'Data tidak ditemukan',
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        }catch(Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id) {
        try{
            $variabel = Variabel::find($id)->delete();
            return $this->successResponse('Data berhasil dihapus', Response::HTTP_OK);
        }catch(Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
