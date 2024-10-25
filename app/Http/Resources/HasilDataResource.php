<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HasilDataResource extends JsonResource
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
            'variabel' => $this->variabel->only('id', 'nama', 'order', 'tipe'),
            'rekap_hasil' => $this->rekap_hasil,
            'konten' => $this->konten,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
