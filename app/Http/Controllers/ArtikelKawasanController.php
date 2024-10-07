<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Shapefile\ShapefileException;
use Shapefile\ShapefileReader;
use App\Http\Traits\ApiResponse;
use App\Models\ArtikelKawasan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class ArtikelKawasanController extends Controller
{
    use ApiResponse;
    public function index() {}

    public function store(Request $request)
    {
        // Validasi input
        $validate = $request->validate([
            'judul' => 'required|string|max:255',
            'shapefile_zip' => 'required|file|mimes:zip,json|max:20480', // Maksimum 20MB
            'konten' => 'required|string',
            'status' => 'required|in:public,private',
            'opd_provinsi_id' => 'required|exists:opd_provinsi,id',
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
                ArtikelKawasan::create([
                    'judul' => $validate['judul'],
                    'konten' => $validate['konten'],
                    'status' => $validate['status'],
                    'opd_provinsi_id' => $validate['opd_provinsi_id'],
                    'file' => $geojsonPath,
                ]);

                return $this->successResponse('Artikel Kawasan berhasil dibuat', 201);
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

                // Simpan informasi ke database
                ArtikelKawasan::create([
                    'judul' => $validate['judul'],
                    'konten' => $validate['konten'],
                    'status' => $validate['status'],
                    'opd_provinsi_id' => $validate['opd_provinsi_id'],
                    'file' => $geojsonPath,
                ]);

                return $this->successResponse('Artikel Kawasan berhasil dibuat', 201);
            }
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Data gagal dibuat', 400);
        } catch (ShapefileException $e) {
            Log::error('ShapefileException: ' . $e->getMessage());
            return $this->errorResponse('Error: ' . $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('Exception: ' . $e->getMessage());
            return response()->json(['error' => 'Error: ' . $e->getMessage()], 500);
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

        return $this->errorResponse('Gagal membuat artikel kawasan', 400);
    }

    private function cleanupTempDir($dir)
    {
        if (File::exists($dir)) {
            File::deleteDirectory($dir);
        }
    }
}
