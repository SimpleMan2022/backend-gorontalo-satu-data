<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SektorSpasialResource extends JsonResource
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
            'sektor' => $this->sektor->only('id', 'nama', 'logo'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
