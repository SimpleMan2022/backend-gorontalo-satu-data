<?php

namespace App\Http\Controllers;

use App\Models\AntarWaktu;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponse;
use Exception;
use Symfony\Component\HttpFoundation\Response;

class AntarWaktuController extends Controller
{
    use ApiResponse;
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'tahun' => 'required',
                'nama' => 'required'
            ]);
            $antarWaktu = AntarWaktu::create($validated);
            return $this->successResponse('Data berhasil dibuat', Response::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
