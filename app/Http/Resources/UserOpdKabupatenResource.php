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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
