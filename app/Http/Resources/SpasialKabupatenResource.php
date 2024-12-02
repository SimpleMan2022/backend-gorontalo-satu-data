<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpasialKabupatenResource extends JsonResource
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
            'olahan_spasial' => $this->olahan_spasial->only('id', 'judul', 'konten', 'status', 'opd_provinsi_id', 'sektor_spasial_id'),
            'kabupaten' => $this->kabupaten->only('id', 'name'),
            'tahun' => $this->tahun,
            'konten' => $this->konten,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
