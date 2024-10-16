<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OpdProvinsiResource extends JsonResource
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
            'lokasi' => $this->lokasi,
            'created_at' => $this->created_at,
            'updated_at' => $this->created_at
        ];
    }
}
