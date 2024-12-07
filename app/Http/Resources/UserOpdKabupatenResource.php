<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserOpdKabupatenResource extends JsonResource
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
            'nama' => $this->nama,
            'opd_kabupaten' => [
                'id' => $this->opd_kabupaten->id,
                'nama' => $this->opd_kabupaten->nama,
                'lokasi' => $this->opd_kabupaten->lokasi,
                'kabupaten' => $this->opd_kabupaten->kabupaten ? $this->opd_kabupaten->kabupaten->only('id', 'name') : null,
                'opd_provinsi' => $this->opd_kabupaten->opd_provinsi ? $this->opd_kabupaten->opd_provinsi->only('id', 'nama') : null,
            ],
            'jabatan' => $this->jabatan,
            'wilayah' => $this->whenLoaded('wilayah', function () {
                if ($this->tipe_wilayah === \App\Models\Desa::class) {
                    return $this->wilayah->load(['kecamatan.kabupaten']);
                } elseif ($this->tipe_wilayah === \App\Models\Kecamatan::class) {
                    return $this->wilayah->load(['kabupaten']);
                }

                return $this->wilayah; // Default jika tidak perlu relasi lebih lanjut
            }),
            'tipe_wilayah' => $tipeWilayahName,
            'user' => $this->user,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
