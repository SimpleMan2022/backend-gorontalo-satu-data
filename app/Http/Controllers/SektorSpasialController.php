<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Traits\ApiResponse;
use App\Models\{SektorSpasial, Sektor};
use App\Http\Resources\SektorSpasialResource;
use Exception;
use Illuminate\Http\Response;

class SektorSpasialController extends Controller
{
    use ApiResponse;
    public function index() {
        try {
            // Memanggil data dan request PerPage
            $per_page = $request->per_page ?? 10;
            $data = SektorSpasial::orderBy('created_at', 'desc')->paginate($per_page);

            // Mengecek apabila terdapat data
            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    SektorSpasialResource::collection($data),
                    'Data sektor berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse(
                    'Data masih kosong',
                    Response::HTTP_NOT_FOUND
                );
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
