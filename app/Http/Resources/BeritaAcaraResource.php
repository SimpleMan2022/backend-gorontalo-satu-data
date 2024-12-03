<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BeritaAcaraResource extends JsonResource
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
            'nama' => $this->nama,
            'opd_kabupaten' => [
                'id' => $this->opd_kabupaten->id,
                'opd_provinsi_id' => $this->opd_kabupaten->opd_provinsi->id,
                'nama' => $this->opd_kabupaten->nama,
                'lokasi' => $this->opd_kabupaten->lokasi,
            ],
            'file' => $this->file,
            'indikator' => $this->indikator->only('id', 'nama', 'status'),
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->created_at
        ];
    }
}
