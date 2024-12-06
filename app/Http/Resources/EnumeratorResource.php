<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnumeratorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        switch ($this->tipe_wilayah) {
            case 'App\Models\Desa':
                $tipeWilayahName = 'Desa';
                break;
            case 'App\Models\Kecamatan':
                $tipeWilayahName = 'Kecamatan';
                break;
            case 'App\Models\Kabupaten':
                $tipeWilayahName = 'Kabupaten';
                break;
            default:
                $tipeWilayahName = 'Unknown';
        }
        
        return [
            'id' => $this->id,
            'email' => $this->user->email,
            'username' => $this->user->username,
            'nama' => $this->nama,
            'jabatan' => $this->jabatan,
            'enumerator_level' => $this->when(
            $this->enumerator_kabupaten->isNotEmpty() || $this->enumerator_provinsi->isNotEmpty(),
            function () {
                // Cek apakah enumerator berada di tingkat kabupaten atau provinsi
                if ($this->enumerator_kabupaten->isNotEmpty()) {
                return 'kabupaten';
                }

                if ($this->enumerator_provinsi->isNotEmpty()) {
                return 'provinsi';
                }

                return 'unknown'; // Jika tidak ditemukan, bisa diisi dengan 'unknown' atau nilai lain
            }
            ),
            'opd' => $this->when(
            $this->enumerator_kabupaten->isNotEmpty() || $this->enumerator_provinsi->isNotEmpty(),
            function () {
                if ($this->enumerator_kabupaten->isNotEmpty()) {
                $opd = $this->enumerator_kabupaten->first()->opd_kabupaten;
                } else {
                $opd = $this->enumerator_provinsi->first()->opd_provinsi;
                }
                return [
                'id' => $opd->id,
                'nama' => $opd->nama,
                ];
            }
            ),
            'wilayah' => $this->whenLoaded('wilayah', function () {
                if ($this->tipe_wilayah === \App\Models\Desa::class) {
                    return $this->wilayah->load(['kecamatan.kabupaten']);
                } elseif ($this->tipe_wilayah === \App\Models\Kecamatan::class) {
                    return $this->wilayah->load(['kabupaten']);
                }

                return $this->wilayah; // Default jika tidak perlu relasi lebih lanjut
            }),
            'tipe_wilayah' => $tipeWilayahName,
            'created_at' => $this->created_at,
            'updated_at' => $this->created_at
        ];
    }
}
