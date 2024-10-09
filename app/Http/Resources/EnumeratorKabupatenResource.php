<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnumeratorKabupatenResource extends JsonResource
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
            'opd_kabupaten_id' => $this->opd_kabupaten,
            'created_at' => $this->created_at,
            'updated_at' => $this->created_at
        ];
    }
}
