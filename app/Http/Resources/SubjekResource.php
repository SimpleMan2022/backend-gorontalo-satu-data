<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubjekResource extends JsonResource
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
            'opd_provinsi_id' => $this->opd_provinsi,
            // 'pivot_indikator' => $this->pivot_indikator_subjek->indikator,
            'indikator' => $this->pivot_indikator_subjek->map(function ($pivot) {
                return [
                    'pivot_id' => $pivot->id,
                    ...$pivot->indikator->only('id', 'nama'),
                    // 'id' => $pivot->indikator_id,
                    // 'pivot_id' => $pivot->id,
                    // 'pivot_id' => $pivot->id,
                    // 'indikator_id' => $pivot->indikator,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
