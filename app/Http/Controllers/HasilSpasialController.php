<?php

namespace App\Http\Controllers;

use App\Http\Resources\HasilDataResource;
use App\Http\Resources\HasilSpasialResource;
use Illuminate\Http\Request;
use Shapefile\ShapefileException;
use Shapefile\ShapefileReader;
use App\Http\Traits\ApiResponse;
use App\Models\HasilSpasial;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

class HasilSpasialController extends Controller
{
    use ApiResponse;
    public function index(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 10;
            $hasilSpasialQuery = HasilSpasial::orderBy('created_at', 'desc');

            if ($opdProvinsiId = $request->query('opd_provinsi_id')) {
                $hasilSpasialQuery->where('opd_provinsi_id', $opdProvinsiId);
            }

            if ($sektorSpasialId = $request->query('sektor_spasial_id')) {
                $hasilSpasialQuery->where('sektor_spasial_id', $sektorSpasialId);
            }

            $data = $hasilSpasialQuery->paginate($perPage);

            if ($data->isNotEmpty()) {
                return $this->successResponseWithDataIndex(
                    $data,
                    HasilSpasialResource::collection($data),
                    'Data Hasil Spasial berhasil diambil',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse('Data masih kosong', Response::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        try {
            $hasilSpasial = HasilSpasial::findOrFail($id);
            return $this->successResponseWithData(
                new HasilSpasialResource($hasilSpasial),
                'Data Hasil Spasial berhasil diambil',
                Response::HTTP_OK
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Data Hasil Spasial tidak ditemukan', Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        // Validasi input
        $validate = $request->validate([
            'judul' => 'required|string|max:255',
            'shapefile_zip' => 'required|file|mimes:zip,json|max:20480', // Maksimum 20MB
            'konten' => 'required|string',
            'status' => 'required|in:public,private',
            'opd_provinsi_id' => 'required|exists:opd_provinsi,id',
            'sektor_spasial_id' => 'required|exists:sektor_spasial,id'
        ]);

        // Inisialisasi variabel tempDir
        $tempDir = null;

        try {
            // Jika file geojson diunggah langsung
            if ($request->file('shapefile_zip')->getClientOriginalExtension() === 'geojson') {
                // Simpan file GeoJSON langsung ke penyimpanan
                $geojsonFile = $request->file('shapefile_zip');
                $geojsonFilename = uniqid('geojson_') . '.geojson';
                $geojsonPath = 'geojson/' . $geojsonFilename;
                Storage::putFileAs('geojson', $geojsonFile, $geojsonFilename);

                // Simpan informasi ke database
                HasilSpasial::create([
                    'judul' => $validate['judul'],
                    'konten' => $validate['konten'],
                    'status' => $validate['status'],
                    'opd_provinsi_id' => $validate['opd_provinsi_id'],
                    'file' => $geojsonPath,
                    'sektor_spasial_id' => $validate['sektor_spasial_id']
                ]);

                return $this->successResponse('Hasil Spasial berhasil dibuat', 201);
            }

            // Jika file zip diunggah
            if ($request->hasFile('shapefile_zip')) {
                // Buat direktori sementara
                $tempDir = storage_path('app/temp/' . uniqid('shapefile_'));
                if (!File::exists($tempDir)) {
                    File::makeDirectory($tempDir, 0755, true);
                }

                // Simpan file zip ke direktori sementara
                $zipFile = $request->file('shapefile_zip');
                $zipPath = $tempDir . '/' . $zipFile->getClientOriginalName();
                $zipFile->move($tempDir, $zipFile->getClientOriginalName());

                // Ekstrak file zip
                $zip = new ZipArchive;
                if ($zip->open($zipPath) === TRUE) {
                    $zip->extractTo($tempDir);
                    $zip->close();
                } else {
                    return $this->errorResponse('Gagal mengekstrak file zip.', 400);
                }

                // Hapus file zip setelah ekstraksi
                unlink($zipPath);

                // Cari file shapefile (.shp, .shx, .dbf, .prj, .cpg)
                $shpPath = $shxPath = $dbfPath = $prjPath = $cpgPath = null;

                foreach (glob($tempDir . '/*') as $file) {
                    $extension = pathinfo($file, PATHINFO_EXTENSION);
                    switch (strtolower($extension)) {
                        case 'shp':
                            $shpPath = $file;
                            break;
                        case 'shx':
                            $shxPath = $file;
                            break;
                        case 'dbf':
                            $dbfPath = $file;
                            break;
                        case 'prj':
                            $prjPath = $file;
                            break;
                        case 'cpg':
                            $cpgPath = $file;
                            break;
                        default:
                            // Log file tambahan jika diperlukan
                            Log::info("File tambahan ditemukan: " . $file);
                            break;
                    }
                }

                // Validasi bahwa file .shp, .shx, dan .dbf ada
                if (!$shpPath || !$shxPath || !$dbfPath) {
                    return $this->errorResponse('File .shp, .shx, atau .dbf tidak ditemukan.', 400);
                }

                // Inisialisasi ShapefileReader dengan file .shp
                $shapefile = new ShapefileReader($shpPath, [
                    'dbf_encoding' => $cpgPath ? trim(file_get_contents($cpgPath)) : 'UTF-8',
                ]);

                // Jika file .prj ada, set proyeksi
                if ($prjPath) {
                    $projection = trim(file_get_contents($prjPath));
                }

                // Mulai konversi ke GeoJSON
                $geojson = ['type' => 'FeatureCollection', 'features' => []];

                while ($record = $shapefile->fetchRecord()) {
                    if ($record->isDeleted()) {
                        Log::info("Record terhapus, dilewatkan.");
                        continue;
                    }

                    Log::info("Record ditemukan: " . json_encode($record->getDataArray()));

                    $geometry = json_decode($record->getGeoJSON());
                    $properties = $record->getDataArray();

                    $feature = [
                        'type' => 'Feature',
                        'geometry' => $geometry,
                        'properties' => $properties,
                    ];

                    $geojson['features'][] = $feature;
                }
                while ($record = $shapefile->fetchRecord()) {
                    if ($record->isDeleted()) {
                        Log::info("Record terhapus, dilewatkan.");
                        continue;
                    }

                    Log::info("Record ditemukan: " . json_encode($record->getDataArray()));

                    $geometry = json_decode($record->getGeoJSON());
                    $properties = $record->getDataArray();

                    $feature = [
                        'type' => 'Feature',
                        'geometry' => $geometry,
                        'properties' => $properties,
                    ];

                    $geojson['features'][] = $feature;
                }


                // Pastikan ShapefileReader tidak memegang handle file
                unset($shapefile);
                gc_collect_cycles(); // Memicu garbage collection

                // Simpan GeoJSON ke penyimpanan
                $geojsonFilename = uniqid('geojson_') . '.geojson';
                $geojsonPath = 'geojson/' . $geojsonFilename;
                Storage::put($geojsonPath, json_encode($geojson, JSON_PRETTY_PRINT));

                // Simpan informasi ke database
                HasilSpasial::create([
                    'judul' => $validate['judul'],
                    'konten' => $validate['konten'],
                    'status' => $validate['status'],
                    'opd_provinsi_id' => $validate['opd_provinsi_id'],
                    'file' => $geojsonPath,
                    'sektor_spasial_id' => $validate['sektor_spasial_id']
                ]);

                return $this->successResponse('Hasil Spasial berhasil dibuat', 201);
            }
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal dibuat', 400);
        } catch (ShapefileException $e) {
            Log::error('ShapefileException: ' . $e->getMessage());
            return $this->errorResponse('Error: ' . $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('Exception: ' . $e->getMessage());
            return $this->errorResponse('Error: ' . $e->getMessage(), 500);
        } finally {
            // Pembersihan direktori sementara menggunakan File facade
            if ($tempDir && File::exists($tempDir)) {
                if (!File::deleteDirectory($tempDir)) {
                    $remainingFiles = File::allFiles($tempDir);
                    foreach ($remainingFiles as $file) {
                        Log::warning("File tersisa di tempDir: " . $file->getRealPath());
                    }
                    Log::error("Gagal menghapus direktori sementara: " . $tempDir);
                } else {
                    Log::info("Direktori sementara berhasil dihapus: " . $tempDir);
                }
            }
        }

        return $this->errorResponse('Gagal membuat Hasil Spasial', 400);
    }

    public function update(Request $request, $id)
    {
        // Validasi input
        $validate = $request->validate([
            'judul' => 'required|string|max:255',
            'shapefile_zip' => 'nullable|file|mimes:zip,json|max:20480', // Maksimum 20MB
            'konten' => 'required|string',
            'status' => 'required|in:public,private',
            'opd_provinsi_id' => 'required|exists:opd_provinsi,id',
            'sektor_spasial_id' => 'required|exists:sektor_spasial,id'
        ]);

        // Temukan Hasil Spasial berdasarkan id
        $artikelKawasan = HasilSpasial::findOrFail($id);

        // Inisialisasi variabel tempDir
        $tempDir = null;

        try {
            // Jika file geojson diunggah langsung
            if ($request->file('shapefile_zip') && $request->file('shapefile_zip')->getClientOriginalExtension() === 'geojson') {
                // Hapus file lama jika ada
                if ($artikelKawasan->file) {
                    Storage::delete($artikelKawasan->file);
                }

                // Simpan file GeoJSON langsung ke penyimpanan
                $geojsonFile = $request->file('shapefile_zip');
                $geojsonFilename = uniqid('geojson_') . '.geojson';
                $geojsonPath = 'geojson/' . $geojsonFilename;
                Storage::putFileAs('geojson', $geojsonFile, $geojsonFilename);

                // Update data Hasil Spasial di database
                $artikelKawasan->update([
                    'judul' => $validate['judul'],
                    'konten' => $validate['konten'],
                    'status' => $validate['status'],
                    'opd_provinsi_id' => $validate['opd_provinsi_id'],
                    'file' => $geojsonPath,
                ]);

                return $this->successResponse('Hasil Spasial berhasil diperbarui', 200);
            }

            // Jika file zip diunggah
            if ($request->hasFile('shapefile_zip')) {
                // Hapus file lama jika ada
                if ($artikelKawasan->file) {
                    Storage::delete($artikelKawasan->file);
                }

                // Buat direktori sementara
                $tempDir = storage_path('app/temp/' . uniqid('shapefile_'));
                if (!File::exists($tempDir)) {
                    File::makeDirectory($tempDir, 0755, true);
                }

                // Simpan file zip ke direktori sementara
                $zipFile = $request->file('shapefile_zip');
                $zipPath = $tempDir . '/' . $zipFile->getClientOriginalName();
                $zipFile->move($tempDir, $zipFile->getClientOriginalName());

                // Ekstrak file zip
                $zip = new ZipArchive;
                if ($zip->open($zipPath) === TRUE) {
                    $zip->extractTo($tempDir);
                    $zip->close();
                } else {
                    return $this->errorResponse('Gagal mengekstrak file zip.', 400);
                }

                // Hapus file zip setelah ekstraksi
                unlink($zipPath);

                // Cari file shapefile (.shp, .shx, .dbf, .prj, .cpg)
                $shpPath = $shxPath = $dbfPath = $prjPath = $cpgPath = null;

                foreach (glob($tempDir . '/*') as $file) {
                    $extension = pathinfo($file, PATHINFO_EXTENSION);
                    switch (strtolower($extension)) {
                        case 'shp':
                            $shpPath = $file;
                            break;
                        case 'shx':
                            $shxPath = $file;
                            break;
                        case 'dbf':
                            $dbfPath = $file;
                            break;
                        case 'prj':
                            $prjPath = $file;
                            break;
                        case 'cpg':
                            $cpgPath = $file;
                            break;
                        default:
                            Log::info("File tambahan ditemukan: " . $file);
                            break;
                    }
                }

                // Validasi bahwa file .shp, .shx, dan .dbf ada
                if (!$shpPath || !$shxPath || !$dbfPath) {
                    return $this->errorResponse('File .shp, .shx, atau .dbf tidak ditemukan.', 400);
                }

                // Inisialisasi ShapefileReader dengan file .shp
                $shapefile = new ShapefileReader($shpPath, [
                    'dbf_encoding' => $cpgPath ? trim(file_get_contents($cpgPath)) : 'UTF-8',
                ]);

                // Jika file .prj ada, set proyeksi
                if ($prjPath) {
                    $projection = trim(file_get_contents($prjPath));
                }

                // Mulai konversi ke GeoJSON
                $geojson = ['type' => 'FeatureCollection', 'features' => []];

                while ($record = $shapefile->fetchRecord()) {
                    if ($record->isDeleted()) {
                        continue;
                    }

                    $geometry = json_decode($record->getGeoJSON());
                    $properties = $record->getDataArray();

                    $feature = [
                        'type' => 'Feature',
                        'geometry' => $geometry,
                        'properties' => $properties,
                    ];

                    $geojson['features'][] = $feature;
                }

                // Pastikan ShapefileReader tidak memegang handle file
                unset($shapefile);
                gc_collect_cycles(); // Memicu garbage collection

                // Simpan GeoJSON ke penyimpanan
                $geojsonFilename = uniqid('geojson_') . '.geojson';
                $geojsonPath = 'geojson/' . $geojsonFilename;
                Storage::put($geojsonPath, json_encode($geojson, JSON_PRETTY_PRINT));

                // Update Hasil Spasial dengan data baru
                $artikelKawasan->update([
                    'judul' => $validate['judul'],
                    'konten' => $validate['konten'],
                    'status' => $validate['status'],
                    'opd_provinsi_id' => $validate['opd_provinsi_id'],
                    'file' => $geojsonPath,
                    'sektor_spasial_id' => $validate['sektor_spasial_id']
                ]);

                return $this->successResponse('Hasil Spasial berhasil diperbarui', 200);
            }

            // Update data lain jika file tidak diunggah
            $artikelKawasan->update([
                'judul' => $validate['judul'],
                'konten' => $validate['konten'],
                'status' => $validate['status'],
                'opd_provinsi_id' => $validate['opd_provinsi_id'],
                'sektor_spasial_id' => $validate['sektor_spasial_id']
            ]);

            return $this->successResponse('Hasil Spasial berhasil diperbarui', 200);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal diperbarui', 400);
        } catch (ShapefileException $e) {
            Log::error('ShapefileException: ' . $e->getMessage());
            return $this->errorResponse('Error: ' . $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('Exception: ' . $e->getMessage());
            return response()->json(['error' => 'Error: ' . $e->getMessage()], 500);
        } finally {
            // Pembersihan direktori sementara
            if ($tempDir && File::exists($tempDir)) {
                if (!File::deleteDirectory($tempDir)) {
                    Log::error("Gagal menghapus direktori sementara: " . $tempDir);
                } else {
                    Log::info("Direktori sementara berhasil dihapus: " . $tempDir);
                }
            }
        }
    }

    public function destroy($id)
    {
        try {
            // Cari artikel berdasarkan ID
            $artikelKawasan = HasilSpasial::findOrFail($id);

            // Hapus file yang terkait jika ada
            if ($artikelKawasan->file) {
                // Cek apakah file yang disimpan merupakan GeoJSON
                if (Str::endsWith($artikelKawasan->file, '.geojson')) {
                    // Hapus file GeoJSON dari penyimpanan
                    if (Storage::exists($artikelKawasan->file)) {
                        Storage::delete($artikelKawasan->file);
                    } else {
                        Log::warning('File GeoJSON tidak ditemukan: ' . $artikelKawasan->file);
                    }
                } else {
                    Log::warning('Format file tidak dikenali: ' . $artikelKawasan->file);
                }
            }

            // Hapus artikel dari database
            $artikelKawasan->delete();

            return $this->successResponse('Hasil Spasial berhasil dihapus', 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Hasil Spasial tidak ditemukan', 404);
        } catch (\Exception $e) {
            Log::error('Exception: ' . $e->getMessage());
            return $this->errorResponse('Error: ' . $e->getMessage(), 500);
        }
    }

    public function multiDestroy()
    {
        try {
            $id = request('id');
            $ids = explode(",", $id);

            $artikelKawasan = HasilSpasial::whereIn('id', $ids)->pluck('id')->toArray();

            $notFoundIds = array_diff($ids, $artikelKawasan);

            if (!empty($notFoundIds)) {
                return $this->errorResponse('Data dengan ID berikut tidak ditemukan: ' . implode(', ', $notFoundIds), Response::HTTP_NOT_FOUND);
            }

            HasilSpasial::whereIn('id', $artikelKawasan)->delete();

            return $this->successResponse('Hasil Spasial berhasil dihapus', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    private function cleanupTempDir($dir)
    {
        if (File::exists($dir)) {
            File::deleteDirectory($dir);
        }
    }
}
