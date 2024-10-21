<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VariabelResource extends JsonResource
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
            'indikator' => $this->indikator->only('id', 'nama', 'status'),
            'sektor' => $this->indikator->sektor->nama,
            'opd_provinsi' => $this->indikator->opd_provinsi->nama,
            'tipe' => $this->tipe,
            'order' => $this->order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
