<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HasilSpasialResource extends JsonResource
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
            'judul' => $this->judul,
            'file' => $this->file,
            'konten' => $this->konten,
            'status' => $this->status,
            'sektor_spasial' => $this->sektor_spasial->only('id', 'nama'),
            'opd_provinsi' => $this->opd_provinsi->only('id', 'nama', 'lokasi'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
