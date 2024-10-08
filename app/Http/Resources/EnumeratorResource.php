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
        return [
            'id' => $this->id,
            'user_id' => $this->user,
            'nama' => $this->nama,
            'jabatan' => $this->jabatan,
            'kabupaten' => $this->when(
                $this->enumerator_kabupaten->isNotEmpty(),
                $this->enumerator_kabupaten->map(function ($kabupaten) {
                    return [
                        'id' => $kabupaten->id,
                        'opd_kabupaten_id' => $kabupaten->opd_kabupaten,
                        'created_at' => $kabupaten->created_at->format('d F Y'),
                        'updated_at' => $kabupaten->updated_at->format('d F Y'),
                    ];
                })
            ),
            'provinsi' => $this->when(
                $this->enumerator_provinsi->isNotEmpty(),
                $this->enumerator_provinsi->map(function ($provinsi) {
                    return [
                        'id' => $provinsi->id,
                        'opd_provinsi_id' => $provinsi->opd_provinsi,
                        'created_at' => $provinsi->created_at->format('d F Y'),
                        'updated_at' => $provinsi->updated_at->format('d F Y'),
                    ];
                })
            ),
            'created_at' => $this->created_at->format('d F Y'),
            'updated_at' => $this->created_at->format('d F Y')
        ];
    }
}
