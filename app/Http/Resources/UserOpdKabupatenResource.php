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
            'opd_kabupaten_id' => $this->opd_kabupaten,
            'user_id' => $this->user,
            'jabatan' => $this->jabatan,
            'created_at' => $this->created_at->format('d F Y'),
            'updated_at' => $this->created_at->format('d F Y')
        ];
    }
}
