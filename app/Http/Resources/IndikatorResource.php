<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IndikatorResource extends JsonResource
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
            'sektor' => $this->sektor->only('id', 'nama'),
            'antar_waktu' => $this->antar_waktu->only('id', 'tahun', 'nama'),
            'opd_provinsi' => $this->opd_provinsi->only('id', 'nama', 'lokasi'),
            'variabel' => $this->variabel->map(function ($variabel) {
                return $variabel->only('id', 'nama');
            }),
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->created_at,
        ];
    }
}
