<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SektorResource extends JsonResource
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
            'logo' => $this->logo,
            'opd_provinsi' => $this->pivot_opd_sektor->map(function ($pivot) {
                return $pivot->opd_provinsi->only('id', 'nama', 'logo');
            }),
            'created_at' => $this->created_at,
        ];
    }
}
