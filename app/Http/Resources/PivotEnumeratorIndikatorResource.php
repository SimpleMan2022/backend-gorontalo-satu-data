<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PivotEnumeratorIndikatorResource extends JsonResource
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
            'enumerator' => $this->enumerator->only('id', 'nama', 'jabatan', 'enumerator_level'),
            'indikator' => $this->indikator->only('id', 'nama', 'status'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
