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
            'olahan_spasial_id' => $this->olahan_spasial,
            'kabupaten_id' => $this->kabupaten,
            'tahun' => $this->tahun,
            'konten' => $this->konten,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
