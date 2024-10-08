<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnumeratorProvinsiResource extends JsonResource
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
            'enumerator_id' => $this->enumerator,
            'opd_provinsi_id' => $this->opd_provinsi,
            'created_at' => $this->created_at->format('d F Y'),
            'updated_at' => $this->created_at->format('d F Y')
        ];
    }
}
