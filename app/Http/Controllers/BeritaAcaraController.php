<?php

namespace App\Http\Controllers;

use App\Http\Resources\BeritaAcaraResource;
use App\Http\Traits\ApiResponse;
use Exception;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use App\Models\BeritaAcara;
use Illuminate\Http\Request;

class BeritaAcaraController extends Controller
{
    use ApiResponse;
    public function index()
    {
        try {
            $data = BeritaAcara::orderBy('created_at', 'desc')->get();
            if ($data->isNotEmpty()) {
                return $this->successResponseWithData($data, 'success', Response::HTTP_OK);
            } else {
                return response()->json([
                    'message' => 'Data masih kosong.'
                ]);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'nama' => 'required|string',
                'opd_kabupaten_id' => 'required|integer',
                'file' => 'required|file|mimes:pdf|max:20480', // Restrict file to only PDF
                'indikator_id' => 'required|integer',
                'status' => 'required'
            ]);

            // Handle file upload (PDF only)
            if ($request->hasFile('file')) {
                $timestamp = now()->format('Ymd_His');
                $originalName = $request->file('file')->getClientOriginalName();
                $lowercaseName = strtolower(str_replace(' ', '_', pathinfo($originalName, PATHINFO_FILENAME)));
                $fileName = "berita_acara_{$timestamp}.pdf";
                $filePath = $request->file('file')->storeAs('berita_acara', $fileName);
                $validated['file'] = $filePath;
            }

            // Convert 'nama' to lowercase before saving
            $validated['nama'] = strtolower($validated['nama']);

            // Create BeritaAcara record
            BeritaAcara::create($validated);

            return $this->successResponse('Data berhasil dibuat', Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal dibuat', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(string $id)
    {
        try {
            $data = BeritaAcara::find($id);

            // Mengecek apabila terdapat data dengan id yang sama
            if ($data) {
                return $this->successResponseWithData(
                    BeritaAcaraResource::make($data),
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

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'nama' => 'required',
                'opd_kabupaten_id' => 'required',
                'file' => 'required',
                'indikator_id' => 'required',
                'status' => 'required'
            ]);

            $berita_acara = BeritaAcara::find($id);
            $berita_acara->update($validated);

            return $this->successResponse('Data berhasil diperbarui', Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal dibuat', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(string $id)
    {
        try {

            // Menghapus data sektor sesuai request $sektor
            $berita_acara = BeritaAcara::find($id);
            $berita_acara->delete();
            return $this->successResponse('successfully deleted data', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
