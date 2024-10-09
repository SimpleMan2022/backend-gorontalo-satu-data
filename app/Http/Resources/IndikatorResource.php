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
            'sektor_id' => $this->sektor_id,
            'sektor' => $this->sektor->nama,
            'antar_waktu_id' => $this->antar_waktu_id,
            'tahun_antarwaktu' => $this->antar_waktu->tahun,
            'nama_antarwaktu' => $this->antar_waktu->nama,
            'opd_provinsi_id' => $this->opd_provinsi_id,
            'opd_provinsi' => $this->opd_provinsi->nama,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->created_at,
        ];
    }
}
